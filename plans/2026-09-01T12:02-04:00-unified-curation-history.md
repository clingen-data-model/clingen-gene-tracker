# Unify replay-safe field history on `Curation`

## Context

`Curation` tracks history for three properties using three different mechanisms, none
of them replay-safe:

| Field | Storage | Current pointer | Writer |
|---|---|---|---|
| status | `curation_curation_status.status_date` (point-in-time, `TIMESTAMP`) | `curations.curation_status_id`, maintained by `UpdateCurrentStatus` | `AddStatus` (queued) |
| classification | `classification_curation.classification_date` (point-in-time, `DATETIME`) | none — computed accessor | `AddClassification` (not queued) |
| expert panel | `curation_expert_panel.start_date`/`end_date` (**interval**, `DATE`) | `curations.expert_panel_id` | `SetOwner` (not queued, **sends email**) |

None has a unique constraint. Idempotency is attempted only in PHP, three different
ways, and only against *values and dates* — never against the source event. Kafka
supplies a perfectly good idempotency key that nothing uses: `incoming_stream_messages`
already has a UNIQUE `key` of `"{report_id}-{payload.date}"`, computed in
`app/DataExchange/Kafka/StoreMessageHandler.php`.

The cost is visible in the repo: four repair commands
(`CleanDuplicateCurationStatuses`, `FixStatusOrder`, `SetCurationStatusId`,
`Services/Utilities/CleanDuplicateCurationStatuses`), two cleanup migrations
(`2020_08_13_163627_*`, `2020_08_13_164815_*`), and 11 currently-failing tests from
`32d5b749` ("Enhanced AddStatus and AddClassification especially for gci:replay.
GT-96") — see `test-needing-fixes.md` clusters B/C/D.

**Outcome:** one dated, source-keyed, DB-enforced idempotent write path for all three
fields; replay of an already-applied event becomes a true no-op (no rows, no email, no
outgoing Kafka message); out-of-order events record history without clobbering current
state; drift becomes self-healing via one rebuild command.

## Why not Venturecraft\Revisionable

It can't be the source of truth, for five reasons — all verified against
`vendor/venturecraft/revisionable`:

1. **No effective-date column, and no way to add one.** `RevisionableTrait` hard-codes
   `'created_at' => new \DateTime()` in four places (`postSave`, `postCreate`,
   `postDelete`). Replayed events carry their own date; there is nowhere to put it.
2. **It observes, it doesn't drive.** Rows are diffed from `getDirty()` after the model
   is already written. You cannot insert a back-dated revision and have current state
   recompute — `restoreRevision()` does the opposite, writing `new_value` back onto the
   model.
3. **It never sees pivot attaches.** `belongsToMany()->attach()` bypasses the parent
   model, so status/classification/owner history is structurally invisible to it.
4. **No uniqueness.** Writes are a plain `DB::table('revisions')->insert()`. Replays
   append.
5. **`getSystemUserId()` returns null** unless `backpack_auth()->check()` or
   `Auth::check()` — i.e. always null on the queue/console paths, which is every GCI path.

Plus: `old_value`/`new_value` are unindexed `text`, so reimplementing
`CurationExporter`'s per-status pivot subqueries against it means unindexable casts in
`WHERE`.

**Keep Revisionable exactly as-is.** It is a live read path via
`backpack/revise-operation` for `ExpertPanelCrudController`, `UserCrudController` and
`AffiliationCrudController` (`app/Http/Controllers/Admin/`), where its assumptions
actually hold — human-authenticated admin edits, write time == effective time. It is
write-only for `Curation` (there is no `CurationCrudController`) and should stay that
way. Add a docblock on `Curation::$revisionCreationsEnabled` saying so.

`activity_log` is likewise unsuitable as a source of truth: `config/activitylog.php`
sets `delete_records_older_than_days => 365`. A prunable table can't back a projection.

## Design

### Two-layer idempotency — state this explicitly in the code

1. **Hard layer (DB unique index).** The same *source event* can never be recorded
   twice. Race-proof, concurrency-proof, replay-proof.
2. **Soft layer (PHP).** A *different* source event asserting the value the timeline
   already holds at that point is not recorded. Affects noise, not correctness. This is
   the pre-`32d5b749` OR semantics.

The current code has only layer 2, implemented three ways. That is the bug.

### The pivots become the event log

Each pivot row *is* a dated event. No new table.

```
curation_curation_status   + source        VARCHAR(32)  ascii NOT NULL
                           + source_event_key VARCHAR(191) ascii NOT NULL
                           + UNIQUE (curation_id, source_event_key)
                           + UNIQUE (curation_id, curation_status_id, status_date)
                           + INDEX  (curation_id, status_date, id)

classification_curation    -- same three columns/indexes, on classification_id/classification_date
curation_expert_panel      -- same, on expert_panel_id/start_date; + missing FKs and curation_id index
```

Notes on the exact shape:

- **`source_event_key` is NOT NULL.** MySQL permits unlimited NULLs in a unique index,
  so a nullable key would let UI writes slip past the constraint — exactly the hole the
  PHP `if` checks leave today.
- **`ascii` charset** keeps the unique index at ~227 bytes instead of ~896 under
  utf8mb4. Free insurance against row-format surprises.
- **`(curation_id, source_event_key)`, not `source_event_key` alone.** One GCI message
  legitimately asserts a status *and* a classification; they live in different tables,
  so the same key can be reused verbatim and stays joinable against
  `incoming_stream_messages.key`.

### Ownership becomes point-in-time; `end_date` is derived

`curation_expert_panel` keeps its columns so `HistoryTable.vue`
(`:items="curation.expert_panels" date-field="start_date"`, `Show.vue:103`) and
`CurationTransferController`'s response shape are untouched — but `end_date` is now
*computed by the projector* as the next event's `start_date`, `null` for the last.

Three bugs die at once:

- `SetOwner::__construct` does `Carbon::parse($endDate)` with `$endDate = null`, which
  yields **now** — so `end_date` is never actually null today.
- `SetOwner::setEndOfOwnership()` calls `updateExistingPivot($currentOwnerId, ...)`,
  which updates **every** row for that panel id, breaking A→B→A ownership.
- The "Repair missing history row for legacy/bad data" branch, which fabricates a
  same-day start/end row on every curation creation (cluster D).

`start_date` stays a `DATE` column; the projector truncates. Per-field date granularity
is an explicit policy, not an accident — see `CurationField::truncatesToDay()` below.

### Source keys

| Writer | `source` | `source_event_key` |
|---|---|---|
| `UpdateCurationFromGeneValidityMessage` | `gci` | `$gciMessage->sourceKey` — byte-identical to `incoming_stream_messages.key` |
| `Curation::boot()` created hook | `created` | `'curation:'.$curation->id` |
| `CurationCurationStatusController@store`, `CurationClassificationController@store`, `CurationTransferController@store` | `ui` | `'ui:'.$field->value.':'.$effectiveAt->toDateString().':'.($valueId ?? 'null')` |
| `BulkCurationProcessor` | `bulk-upload` | `'bulk:'.$uploadId.':'.$rowNumber` |
| Backfill | `backfill` | `'legacy:'.$pivotTable.':'.$pivotRowId` |

Add one method to `app/Gci/GciMessage.php`:

```php
public function getSourceKey(): string
{
    return $this->payload->report_id.'-'.$this->payload->date;
}
```

That single method is what makes `gci:replay` a no-op without threading
`IncomingStreamMessage` through `ReplayGciEventsForCuration` — `GciMessage` already
holds both components.

Consequence worth noting: because the created-hook key is `'curation:'.$id`, the double
dispatch in `Curation::boot()` can fire any number of times and still produce exactly
one status row and one owner row. **Cluster D dies structurally, not by a guard** — a
guard is precisely what `SetOwner`'s `isDirty('expert_panel_id')` already is, and it is
what fails on a freshly-created model.

### The projector, and why replay produces no side effects

`app/Actions/Curations/ProjectCurationField.php` (use `lorisleiva/laravel-actions`,
matching `app/Actions/NotifyPhenotypeAdded.php`), per field, inside a transaction with
`Curation::whereKey($id)->lockForUpdate()` to serialize queue workers:

1. Load the field's rows ordered by `(date, id)`.
2. For expert panel only: recompute every `end_date` from the next row's `start_date`.
3. `$winner = $rows->last()`.
4. **`if ($curation->{$column} !== $winner?->value_id) { $curation->update(...); }`**

Step 4 must compare, never blind-write. `Model::save()` skips `performUpdate()` entirely
when nothing is dirty, so no `updated` event fires — which means
`MakeCurationUpdatedStreamMessage` never runs and `MakeGtGciSyncMessage`'s `isDirty()`
guards all evaluate false. **That is the entire mechanism by which replay stops emitting
outgoing Kafka messages.** Write a regression test that asserts it directly.

Steps 1–3 are the out-of-order answer: an event dated before the newest known event gets
its row (history is correct) but loses the `$rows->last()` comparison, so the
denormalized column is untouched.

### Side effects move to projection changes

**Rule: side effects fire on a *projection change*, never on *event ingestion*.**

- The transfer email currently lives inside `SetOwner::handle()`'s `DB::transaction` —
  so a rolled-back transaction can still have sent mail. Move it to
  `app/Listeners/Curations/SendTransferNotification.php`, listening for a new
  `App\Events\Curation\CurrentOwnerChanged` dispatched only from projector step 4.
- Outgoing Kafka listeners (`MakeCuration{Created,Updated,Deleted}StreamMessage`,
  `MakeGtGciSyncMessage`) stay as they are; they become replay-safe via the
  compare-before-write above.
- Belt and braces: add `config('curations.replaying')`, mirroring the existing
  `config('app.bulk_uploading')` pattern in `Curation::boot()`, set by `gci:replay` and
  the rebuild command. A safety net, not the mechanism.

## Stages

Separate commits, per the one-commit-per-change convention.

### Stage 0 — independent safety fixes

`app/DataExchange/Kafka/StoreMessageHandler.php`:

- Return early when `!$storedMessage->wasRecentlyCreated` — today the chain continues to
  `SuccessfulMessageHandler` and re-dispatches `Received` even for a duplicate row, so
  the storage dedup buys nothing.
- Replace the bare `die;` on payload mismatch with `StreamError::create(...)` +
  `Log::error` + `return`. A `die` in a long-running consumer kills the worker mid-loop
  with no offset commit and no supervision signal.

This does **not** affect `gci:replay`, which reads `IncomingStreamMessage` directly and
bypasses the handler chain — transport dedup at the transport, domain idempotency in the
domain.

### Stage 1 — schema + dedupe + backfill (no behavior change)

- `database/migrations/*_add_source_keys_to_curation_history_pivots.php` — adds the
  three columns and indexes above, plus the missing FKs and `curation_id` index on
  `curation_expert_panel`.
- **Dedupe must run before the unique indexes are added**, or the migration fails on
  existing duplicates. Reuse the logic in
  `app/Services/Utilities/CleanDuplicateCurationStatuses.php`.
- `app/Console/Commands/Curations/BackfillHistorySourceKeys.php` —
  `curations:backfill-source-keys {--chunk=500} {--dry-run}`, invoked by a one-line
  migration. Chunked and re-runnable; do **not** follow the
  `2021_04_05_160324_migrate_curation_expert_panel_data.php` precedent of
  `Curation::all()` inside a migration.
  - Effective date: the pivot's existing date column. That is what it was always for.
  - Key: `'legacy:{table}:{id}'`, `source = 'backfill'`.
  - Ordering tiebreak: the pivot `id` (insertion order), which is what
    `curationStatuses()` should have tiebroken on all along.
  - Expert panel: **discard existing `end_date` values and re-derive.** They are
    known-bad from the `Carbon::parse(null)` bug. This is a deliberate repair.
- `--dry-run` must report, per curation, how many rows would change and how many
  curations' recomputed `curation_status_id` / `expert_panel_id` differ from what is
  stored. That count is the existing drift; review it before letting the projector
  correct it.

### Stage 2 — unified writer + projector

New:

- `app/Curations/CurationField.php` — backed enum (`Status`, `Classification`,
  `ExpertPanel`) with `column()`, `pivotTable()`, `valueColumn()`, `dateColumn()`,
  `truncatesToDay()`, `collapsesConsecutiveDuplicates()`, `isInterval()`.
  `truncatesToDay()` is `true` for status only — `AddStatus` truncated with
  `startOfDay()` before `32d5b749` and `AddClassification` never did, and
  `UpdateFromStreamMessageTest::uses_status_name_and_date_if_oject` asserts both
  behaviours. Preserve the asymmetry as policy, not accident.
- `app/Actions/Curations/RecordCurationFieldEvent.php` — `handle(Curation, CurationField,
  ?int $valueId, Carbon $effectiveAt, string $source, string $sourceKey, array $properties = [])`.
  Hard layer: `firstOrCreate` on `(curation_id, source_event_key)`, catching
  `QueryException` on duplicate key as "already applied". Soft layer:
  `valueAt($field, $effectiveAt) === $valueId` → skip. Then run the projector for that
  field only.
- `app/Actions/Curations/ProjectCurationField.php` — as described above.
- `app/Events/Curation/CurrentOwnerChanged.php` + `app/Listeners/Curations/SendTransferNotification.php`.

Changed:

- `app/Curation.php` — add `valueAt(CurationField, $at)` (generalizing the existing
  `classificationBefore()`, which stays as a thin wrapper) and `latestStatusRow()`.
  Restore the `curationStatuses()` third tiebreak to `curation_curation_status.id DESC`;
  `curation_status_id DESC` (from `32d5b749`) assumes status ids encode workflow order,
  which is not a contract. Delete the `debug_backtrace()` guard in
  `setExpertPanelIdAttribute()` — the projector is now the only writer, enforced by
  construction rather than by inspecting the call stack.
- `AddStatus`, `AddClassification`, `SetOwner` → thin wrappers over
  `RecordCurationFieldEvent`, keeping their constructor signatures so
  `BulkCurationProcessor`, the two history controllers and the existing tests keep
  working. All six `isExisting*`/`isCurrent*` private methods deleted; `SetOwner`'s
  `setEndOfOwnership()`/`addNewOwner()`/email deleted.
- `UpdateCurrentStatus` → thin wrapper over the projector (still called by
  `CurationCurationStatusController@update` and `@destroy`).
- `UpdateClassification` → amend the row then re-project, instead of `$pivot->update()`.
- Both history controllers' `@destroy` — delete the row *and re-project*, so the
  denormalized column follows. `CurationClassificationController@destroy` currently
  re-projects nothing (harmless today, wrong once ordering matters).

Fixes clusters B, C and D.

### Stage 3 — GCI wiring

- `app/Jobs/UpdateCurationFromGeneValidityMessage.php`:
  - `addStatus()`, `addClassification()`, `transferRecord()` pass
    `$this->gciMessage->sourceKey` and `source = 'gci'`.
  - `transferRecord()` uses the message date, **not `Carbon::now()`** — today a replayed
    transfer stamps `start_date` with wall-clock now.
  - The unconditional `$this->curation->update(['gdm_uuid', 'affiliation_id', 'moi_id',
    'mondo_id'])` at the top of `handle()` is the remaining stale-clobber: replaying an
    old message overwrites current values with older ones. These three fields are out of
    scope for history, so guard them with a watermark instead — add
    `curations.gci_event_watermark` (`dateTime`, nullable) and skip the scalar update
    when `$gciMessage->messageDate < $curation->gci_event_watermark`. `gdm_uuid` stays
    unconditional but guarded by `is_null($curation->gdm_uuid)`.
  - Keep the `Log::warning`s from `8011052b`, but stop NULLing `affiliation_id`/`moi_id`
    on a failed lookup — an unresolvable lookup is not an assertion that the value is
    null. Add a `StreamError` row so it is visible outside the log file.
- `app/Jobs/ReplayGciEventsForCuration.php` — order by the **message date**
  (`payload->>'$.date'`), not `created_at, id`. Storage order is arrival order; replay
  should be in event order. Set `config('curations.replaying')` for the duration.
- `app/Jobs/Curations/AddClassification` is dispatched with `dispatch()` while
  `AddStatus` uses `dispatchSync()` in the same method — make both sync.

### Stage 4 — retire the repair machinery

Delete `app/Console/Commands/FixStatusOrder.php`,
`app/Console/Commands/SetCurationStatusId.php`,
`app/Console/Commands/CleanDuplicateCurationStatuses.php`, and
`app/Services/Utilities/CleanDuplicateCurationStatuses.php`. Leave the two 2020-08-13
cleanup migrations — they are already-run history.

Replace with `app/Console/Commands/Curations/RebuildProjections.php` —
`curations:rebuild-projections {curation?} {--field=} {--chunk=500}`, which recomputes
every `end_date` and every denormalized pointer from the pivot rows. Drift becomes
self-healing instead of requiring a new repair command each time.

## Out of scope (deliberately)

- **`affiliation_id`, `moi_id`, `mondo_id` history.** Scoped out; the watermark in
  Stage 3 covers the replay-clobber risk without adding history. Note that
  `8011052b`'s "log for Affiliation ID and MoI" is `Log::warning` diagnostics, not
  persisted history — if history is wanted later, these three slot into the same enum.
- **Other models.** `ExpertPanel`/`User`/`Affiliation` are adequately served by
  Revisionable + Backpack revise. `Phenotype`/OMIM is the same *shape* (external system
  asserting dated facts) but OMIM ingest is batch, full-snapshot and monotonic, so the
  out-of-order and duplicate-delivery problems that motivate this design don't bite; the
  right pattern there is a snapshot/diff log.
- Flagged, not fixed: `app/Listeners/Genes/UpdateCurations.php::handle()` has an **empty
  body** — gene symbol changes are announced and then dropped.
  `MakeGtGciSyncMessage::archiveChanged()` uses `wasChanged('archived_at')`, which on a
  no-op save returns the *previous* save's changes. `app/Jobs/Gci/UpdateGciCurationFromStreamMessage.php`
  is a second consumer of the same stream with maps duplicating
  `GciStatusMap`/`GciClassificationMap`, and the two disagree on what `unpublished`
  means — check whether it is still wired up.

## Test contract

Three rules, stated separately in the code:

1. Same source event applied twice → no-op *(new; DB-enforced)*.
2. A new event asserting the value the timeline already holds at that point → not
   recorded. Re-adding the same status on a later date is a no-op *(the pre-`32d5b749`
   contract)*.
3. A new event asserting a different value → recorded, regardless of date, **including
   dates earlier than the newest known event**.

Rule 1 is what `32d5b749` was actually reaching for; the source key is the tool that
wasn't available then. With it in place, rule 2 costs nothing.

**Must go green unchanged — these are the specification, do not rewrite them:**
`tests/Unit/Jobs/Curations/AddStatusTest.php`,
`tests/Unit/Jobs/Curations/AddClassificationTest.php`,
`tests/Unit/Listeners/Curations/UpdateFromStreamMessageTest.php` (all cases),
`tests/Unit/Models/CurationTest.php::adds_a_curation_expert_panel_record_when_expert_panel_id_changed`,
`tests/Feature/End2End/Curations/CurationTransferTest.php`.

**Legitimately change (two only, both ordering-related):**
`CurationTest::curation_belongs_to_many_curation_statuses` and
`CurationCurationStatusControllerTest::test_relates_new_status_to_curation` — switch
from positional `->first()`/`->last()` to the explicit `latestStatusRow()`. The
structural point is that relation ordering stops being load-bearing: the projector, not
`curationStatuses()->first()`, determines `curation_status_id`.

**New:**

1. `tests/Unit/Actions/Curations/RecordCurationFieldEventTest.php` — same key twice → one
   row; same value later date → no row; different value **earlier** date → row inserted
   and `curations.curation_status_id` unchanged.
2. `tests/Unit/Actions/Curations/ProjectCurationFieldTest.php` — A→B→A ownership yields
   three rows with contiguous disjoint intervals and `end_date = null` only on the last;
   inserting a mid-timeline owner event re-derives the surrounding `end_date`s;
   projecting an unchanged value issues zero UPDATEs (assert via `Event::fake` on
   `App\Events\Curation\Updated`).
3. `tests/Feature/Gci/ReplayIsIdempotentTest.php` — **the acceptance test.** Seed a
   curation plus N `incoming_stream_messages` (reuse `tests/files/gci_messages/*.json`),
   run `gci:replay`, snapshot all three pivots + `stream_messages` + the `curations` row,
   replay five more times, assert byte-identical snapshots plus
   `Mail::assertNothingSent()`.
4. `tests/Feature/Gci/OutOfOrderReplayTest.php` — replay the same messages in **reverse**
   order; final projected state must be identical to forward order. Nothing tests this
   today and it is the property that makes replay actually safe.
5. `tests/Unit/DataExchange/Kafka/StoreMessageHandlerTest.php` — duplicate key →
   `Received` not dispatched; mismatched payload → `StreamError` created, process not
   killed.
6. `tests/Feature/Curations/RebuildProjectionsTest.php` — corrupt the pivots, rebuild,
   assert full recovery. This replaces the four deleted repair commands.

## Verification

Podman, per `docker-compose.yml` (`gt-app`, `gt-db`, `gt-queue`, `gt-redis`):

```
podman-compose exec app php artisan test
podman-compose exec app vendor/bin/phpcs --standard=ruleset.xml app
podman-compose exec app vendor/bin/phpmd app text phpmd.xml
```

Baseline is 21 failed / 3 skipped / 347 passed. Clusters B/C/D (11 tests) must go green;
A/E/F/G (10 tests) are unrelated and stay red.

End-to-end replay check:

```
podman-compose exec app php artisan migrate
podman-compose exec app php artisan curations:backfill-source-keys --dry-run   # review the drift report
podman-compose exec app php artisan curations:backfill-source-keys
podman-compose exec app php artisan curations:rebuild-projections
# pick a curation with a rich message history:
podman-compose exec app php artisan tinker --execute="dd(App\IncomingStreamMessage::selectRaw('gdm_uuid, count(*) c')->whereNotNull('gdm_uuid')->groupBy('gdm_uuid')->orderByDesc('c')->limit(5)->get());"
podman-compose exec app php artisan gci:replay {id} -vvv
podman-compose exec app php artisan gci:replay {id} -vvv
```

Between the two replay runs, row counts in all three pivots, `stream_messages` and
`emails` (`app/Listeners/Mail/StoreMailInDatabase.php` persists sent mail, so it is a
convenient assertion target) must be identical. `dcdb` opens a mysql shell for spot
checks.

Acceptance gate for the backfill: run `gci:replay all` against a copy of production data
and diff the projected `curations` columns before/after. That diff is the drift the
system has accumulated; review it rather than assuming it is empty.

## Housekeeping

Per the working conventions: branch `feat/unified-curation-history`, copy this plan to
`./plans/<date -Iminutes>-unified-curation-history.md` in the first commit. Neither
`CLAUDE.md` nor `.github/copilot-instructions.md` exists in this repo yet — create both,
documenting the two-layer idempotency contract, the source-key convention, and the rule
that side effects fire on projection changes rather than event ingestion.
