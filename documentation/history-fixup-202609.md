# Curation history: what was wrong, what changed, and how to repair the data

September 2026. Branch `feat/unified-curation-history`.

This covers the unification of curation field history, the historical data problems
that surfaced while doing it, and the Artisan commands written to repair them.

**Only step 2 has been applied. Everything else is still a dry run, and nothing here
has touched production.** The figures below come from dry runs on the development
database, with two exceptions: `gci:replay`, which has no dry run and was rehearsed
inside a rolled-back transaction, and `curations:attribute-history-sources`, which was
run for real against the development database — see step 2 for what it did and how the
result was checked.

---

## 1. What was wrong

### 1.1 Three mechanisms, three idempotency checks, none replay-safe

`Curation` tracked history for three fields in three different shapes:

| field | storage | current value | writer |
|---|---|---|---|
| status | `curation_curation_status.status_date`, point-in-time | `curations.curation_status_id` | `AddStatus` (queued) |
| classification | `classification_curation.classification_date`, point-in-time | computed accessor | `AddClassification` (not queued) |
| expert panel | `curation_expert_panel.start_date`/`end_date`, **interval**, `date` granularity | `curations.expert_panel_id` | `SetOwner` (not queued, sent email) |

None had a unique constraint. Idempotency was attempted only in PHP, three different
ways, and only against values and dates — never against the source event. Kafka
already supplied a perfectly good idempotency key that nothing used:
`incoming_stream_messages.key`, i.e. `"{report_id}-{payload.date}"`.

The cost was visible in the repo: four repair commands (`curations:order-statuses`,
`curations:set_current_status_id`, `curations:clean-statuses`, and the service behind
it), two cleanup migrations, and 11 failing tests from commit `32d5b749`.

### 1.2 Specific defects found

**`AddClassification` dropped genuine changes.** The dedup guard read:

```php
return $classification->id == $this->classification->id
    && $classification->pivot->classification_date = $this->date;   // assignment, not comparison
```

The `=` always evaluates truthy, so the condition collapsed to "has this
classification ever been recorded for this curation?" — and it was OR'd into an early
return. Every return to a previously-seen classification was silently discarded.
Definitive → Disputed → Definitive kept two rows.

**Ownership intervals were never left open.** `SetOwner::__construct` did
`Carbon::parse($endDate)` with `$endDate = null`, which yields *now*, so `end_date`
was always set. `setEndOfOwnership()` called `updateExistingPivot($panelId, ...)`,
which updates *every* row for that panel, so A → B → A closed both A tenures. And the
"repair missing history row" branch inserted a spurious same-day start/end row for
every newly created curation.

**Replaying a message re-ran everything.** `StoreMessageHandler` deduplicated the
*storage* with `firstOrCreate`, then called `parent::handle()` unconditionally, so a
redelivered message re-dispatched `Received` and re-ran every listener. It also called
`die()` on a key/payload mismatch, killing the consumer mid-loop.

**Old messages clobbered current values.** `UpdateCurationFromGeneValidityMessage`
began with an unconditional `update()` of `gdm_uuid`, `affiliation_id`, `moi_id`,
`mondo_id`. Replaying an old message overwrote current values with stale ones. Its
`transferRecord()` used `Carbon::now()` rather than the message date, and wrote a
hardcoded transfer note naming test panels in the wrong direction.

**Status dates were truncated to the day.** `AddStatus` called `startOfDay()` on
arrival, discarding times GCI had supplied. A curation that moved twice in one day
left rows nothing could order, so "which status is current" fell to a tiebreak.

### 1.3 Data damage that followed

Measured on the development database.

| problem | scale |
|---|---|
| classification rows missing (GCI-linked curations) | ~3,250 rows across 2,748 of 3,934 curations |
| status rows missing (message-derived estimate) | ~1,850 rows across 842 curations |
| curations with status history but **no `Uploaded` row** | 2,008 |
| curations whose stored status their history cannot account for | 7 |
| curations whose stored current status disagrees with history | 9 |
| duplicate status rows / duplicate ownership rows / orphan ownership rows | 142 groups / 13 groups / 1 |
| status rows truncated to midnight | 17,700 of 25,614 |
| curations not starting at `Uploaded` | 2,283 |
| status transitions outside the state machine | ~42% |

### 1.4 Two things that turned out **not** to be bugs

**Commit `b1c0100a` (`FixStatusOrder`) did not invert its own intent.** Both branches
move the `Uploaded` row *earlier*, which is what was wanted. But it could never fix
the case it was written for: `updateExistingPivot()` only updates rows that already
exist and never inserts, and its selector required `curation_status_id = 1`, which
none of the 2,008 affected curations have. It was a no-op for them.

**Statuses dated before their curation was created are usually legitimate.** 246 of
the 280 such curations have `Uploaded` dated correctly at creation; it is another
status that legitimately predates the tracker record, because the GDM was curated in
the GCI before being uploaded here. Only two curations in the entire database have a
status dated more than five years before creation, and one of those (AVPR1A, a
provisional dated 2011-03-01) came verbatim from a GCI message's `status.date` and is
wrong at source.

---

## 2. What changed in the code

- **One write path.** `App\Actions\Curations\RecordCurationFieldEvent` for all three
  fields. `AddStatus`, `AddClassification` and `SetOwner` are thin wrappers kept
  because callers already knew those names. Per-field policy lives in
  `App\Curations\CurationField`.
- **Two-layer idempotency.** Hard layer: every history row carries a
  `source_event_key`, unique per `(curation_id, source_event_key)`, so the same source
  event cannot be recorded twice even under concurrency. Soft layer: a *different*
  event asserting the value the timeline already holds is not recorded.
- **Ownership is point-in-time.** `end_date` is derived by the projector from the next
  row rather than maintained imperatively.
- **The projector compares before writing.** `App\Actions\Curations\ProjectCurationField`
  leaves the model clean when nothing changes, so no `Curation\Updated` fires and no
  outgoing stream message is produced. This is the whole mechanism by which replay is
  silent — never "refresh" a model by writing identical values back.
- **Side effects fire on projection changes.** The transfer notification moved out of
  `SetOwner`'s transaction onto `Curation\CurrentOwnerChanged`.
- **Full timestamps.** Truncation removed; `curation_expert_panel` widened to
  `datetime`. Midnight now means *time unknown*, not midnight.
- **Watermark for history-less fields.** `curations.gci_event_watermark` guards
  `affiliation_id`, `moi_id` and `mondo_id` against stale overwrites. It records what
  has been consumed rather than anything about the curation, so it is written outside
  the model event lifecycle; writing it through the model would announce a precuration
  change to the data exchange for every message consumed. The scalar update beside it
  compares before writing, for the same reason.
- **The state machine exists.** `App\Curations\StatusTransitions`, as data. Nothing
  enforces it; it is there to be measured against.

---

## 3. Repairing the data

### Order

Run in this order. The reasoning matters more than the list — several of these
interact.

| # | command | why here |
|---|---|---|
| 1 | `curations:restore-status-timestamps` | Everything downstream orders rows by date. Do this first so dedup, projection and ordering all work on real times rather than midnight. |
| 2 | `curations:attribute-history-sources` | Gives a legacy row the key its real source event would have produced, so the replay in the next step recognises it on the source-key index rather than on the value/date index behind it. Needs step 1: matching is to the second, and a row still at midnight matches nothing. |
| 3 | `gci:replay all` | Recovers the bulk of the missing status and classification rows. Needs step 1 first so its dedup compares against correctly-timed rows. |
| 4 | `curations:backfill-status-history-from-revisions` | Fills gaps GCI cannot: curations with no messages, where `revisions` is the only witness. |
| 5 | `curations:impute-uploaded-status` | Dates the imputed row from `min(status_date)`, so history must be as complete as it is going to get before this runs. |
| 6 | `curations:rebuild-projections` | Recomputes derived values from the repaired history. Belt and braces — each write above already projects. |
| 7 | `curations:audit-status-transitions` | Measure the result. Its pre-repair figures were taken against day-truncated data where same-day sequences could not be ordered at all. |

Every command except `gci:replay` takes `--dry-run`; review each dry run before
applying it. `gci:replay` has no dry run and writes immediately — see step 3 for how
to rehearse it safely.

### 1. `curations:restore-status-timestamps`

Recovers the time of day for status rows truncated to midnight, from two sources: the
GCI messages still stored in `incoming_stream_messages`, and the row's own
`created_at` where the row was written the same day it is dated.

```
10821 status row(s) would be given a time:
  gci message                                 | 6403
  row write time                              | 4418
  left at midnight (time unknown)             | 4840
  left alone, day had a row we could not time |  314
  skipped, duplicate of an existing row       |  829

10 curation(s) would change current status once their rows could be ordered.
```

Three rules in there were forced by the data, and each was caught by a dry run:

- **The time comes from a message's emission date, not its `status.date`.** GCI fills
  `status.date` for `approved` messages with a synthetic `16:00:00Z`, which sorts
  *after* the publish that really followed it. Using it moved 199 curations off
  Published.
- **The last assertion of the day wins, not the first.** Where a status was asserted
  twice in a day only one row survived the old dedup, and that row has to stand for
  where the curation ended up.
- **Times are applied a whole day at a time.** Timing some rows of a day and not
  others is worse than timing none: the untimed ones sort to the front of that day
  regardless of when they happened. This is how a hand-entered, backdated `Published`
  row ended up behind two GCI rows that could be timed.

**Check before applying:** the 10 curations that change current status. They include
genuine advances as well as demotions, which is what you want to see.

### 2. `curations:attribute-history-sources`

```
php artisan curations:attribute-history-sources --dry-run
php artisan curations:attribute-history-sources
```

Every row that predates source keys carries `source = 'backfill'` and a key derived
from its own id. That is unique and honest, but it says only "this row was here
already", so the replay in step 3 cannot recognise the row as the event it is and
falls through to the value/date index instead.

Two kinds of evidence are accepted, and nothing else:

- a stored GCI message asserting the row's value at the row's exact instant. For
  status the instant may be either the message's `status.date`, which the live
  writer records, or its emission time, which step 1 writes -- both are instants a
  writer could legitimately have left on the row;
- a revision recording the value with a `user_id`. `getSystemUserId()` returns null
  on every queue and console path, so a user is positive evidence of a human in the
  UI. Classification has no column on `curations`, so no revision can speak for it.

Anything ambiguous keeps `backfill`, which afterwards means "we looked and could not
tell" rather than "we never asked". Two rows that resolve to the same key are both
left alone: one message asserting a value at two instants could have written either,
and picking one is a guess. Values and dates are never touched, so nothing here can
change a projection.

**This step has been applied to the development database.** 13,419 of the 39,314 rows
carrying a placeholder were attributed:

```
gci, matched a stored message                   10915
ui, matched a revision with a user               2504
left as backfill, no evidence                   24173
left as backfill, more than one message matched  1014
skipped, key already taken by another row         708
```

| table | gci | ui | imputed | revision-backfill | backfill |
|---|---|---|---|---|---|
| `curation_curation_status` | 13,185 | 2,173 | 2,025 | 6 | 15,924 |
| `classification_curation` | 4,743 | — | — | — | 1,708 |
| `curation_expert_panel` | 3 | 331 | — | — | 8,263 |

The 708 skipped as "key already taken" are legacy rows whose event the live writer had
already recorded properly; they are duplicates, and leaving them as `backfill` is the
right outcome. Ownership attributes almost entirely to the UI: **only three ownership
rows in the whole database came from a GCI transfer**, which is worth a second look if
you expected GCI transfers to be a meaningful share of ownership history.

**Verification.** The three pivots were dumped beforehand, restored into a scratch
schema and diffed row by row against the result: zero value or date changes in any of
the three tables, zero rows added or removed, and exactly 13,419 source keys rewritten.
Re-running attributes nothing further, so the command is idempotent on real data as
well as in test.

**It was run out of order and should be run again.** Step 1 has not been applied, so
matching had only the rows whose timestamps were already precise — chiefly
`classification_date`, which was `datetime` all along, and the status rows written by
the live GCI path since the schema migration. 6,948 status rows still sit at midnight
and can match nothing until step 1 restores their time of day; the command reports that
count on every run. Expect a further tranche of the remaining 25,895 placeholder rows
to attribute once step 1 has run.

### 3. `gci:replay all`

Not new, but it now recovers far more than it used to, because dedup works at second
precision instead of day precision.

Demonstrated on one curation (CSNK2B, 3778) inside a rolled-back transaction: status
history went from **3 rows to 11**, recovering an entire missing 2021 arc, correctly
interleaved with the two pre-existing non-GCI rows and with no duplicates. A second
replay changed nothing at all.

`gci:replay all` covers every curation with a `gdm_uuid`. There is no `--dry-run`; to
rehearse one curation, wrap `ReplayGciEventsForCuration` in a transaction from tinker
and roll it back, which is how the figures above were obtained.

**Caveat:** a replay that makes *genuine* changes still fires `Curation\Updated`, so
it puts outgoing `stream_messages` on the exchange. Not one per recovered row — one
per change to the curation record itself, which is far fewer: replaying curation 1201
recovered 18 status rows and produced 6 messages, and curation 551 recovered 8 rows
and produced 2. Across ~3,900 GCI-linked curations that is still thousands of
messages. That is arguably correct (downstream should hear about real changes), but
for a bulk historical backfill you may want the opposite.
`config('curations.replaying')` exists as a guard and is honoured by
`SendTransferNotification`, but **is not yet wired to the stream-message listeners**.
Decide this before running it across the database.

### 4. `curations:backfill-status-history-from-revisions`

For curations whose stored status their history cannot account for — the pointer says
Published while the only row is the `Uploaded` one from creation. Revisionable logged
every `curation_status_id` change, so the transitions are recoverable even though the
rows are not.

```
9 curation(s) with status history to recover.
11 history row(s) would be recovered.
Every candidate kept the status it had; history now accounts for it.
```

Deliberately narrow: only curations with that disagreement. Of the 13,309 status
revisions in the table, the great majority correspond to changes that *did* write
history, and replaying those would add rows dated when the pointer was written rather
than when the status changed.

No candidate changes its current status. Recovered rows carry the revision's own
timestamp, so CACNA1C's two revisions on 2023-03-23 stay in the order they were made
rather than collapsing to one date and being separated by workflow rank.

**Check before applying:** rows flagged `REVIEW: migration date` (1672, 3940, 5313)
are dated `2021-05-21`, when migration `2021_05_17_190805` rewrote the pointer — not
when the status changed. For 5313 that is its only evidence, and it has 7 GCI
messages that likely carry the true date, so run step 3 first and re-check whether it
still needs this.

### 5. `curations:impute-uploaded-status`

Gives back the `Uploaded` row to curations whose history never recorded one.

```
2025 Uploaded row(s) would be recorded, dated by:
  created_at            | 1274
  first recorded status |  751

1 curation(s) would change current status:
  7287 | DISP1 | 4 -> 7 | drift already pending
```

The date is the earliest moment the curation can be evidenced to have existed:
`created_at`, or the first recorded status where that predates it. **37% need the
fallback** — `created_at` alone would place `Uploaded` after statuses that already
precede it, reintroducing the artefact described in §1.4.

2,025 rather than 2,008 because this includes soft-deleted curations.

The one status change is labelled `drift already pending`: recording anything runs the
projector, which applies corrections that were already outstanding. The report
separates that from changes the imputation itself causes, and none here are.

### 6. `curations:rebuild-projections`

Recomputes every derived value — ownership `end_date`s and the denormalized current
value columns — from the history rows. Replaces `curations:order-statuses`,
`curations:set_current_status_id` and `curations:clean-statuses`, which have been
deleted.

Pre-repair, `--dry-run` reported 9 curations whose stored current status disagreed
with history. After steps 1–5 that should be down to the ones flagged above.

**A note on same-day ties.** Where two rows still share a timestamp, the tie breaks on
`curation_status_id`, treating it as a workflow rank. This was chosen against the
data: breaking ties on insertion order instead would change the current status of 99
curations, 98 of them downward and 78 away from Published, whereas workflow rank
changes 9. The assumption is sound for the linear path `1→2→3→4→5→6→9` but not for
`7 Recuration assigned` and `8 Retired Assignment`, which sit off it — 78 of ~4,740
same-date tie pairs involve those two. Restoring timestamps (step 1) shrinks the
number of ties that need breaking at all.

### 7. `curations:audit-status-transitions`

Measures recorded history against `App\Curations\StatusTransitions`. Reports; does not
repair. Accepts a curation id for the full annotated sequence of one curation, and
`--ordering=insertion` for the alternative sequencing.

Pre-repair baseline:

```
Audited 8448 curation(s), 17166 transition(s), sequenced by rank order.
  legal        | 8453 | 49.2%
  repeat       | 1531 |  8.9%
  not in graph | 7182 | 41.8%
3380 curation(s) have at least one transition outside the graph.
2008 curation(s) have status history but no Uploaded row at all.
```

A transition outside the graph means either the graph is incomplete or the steps
between were never recorded, and those are indistinguishable from the history alone —
which is why this reports rather than repairs.

---

## 4. Decisions still open

**The state machine is probably incomplete.** These transitions are common in the data
but absent from the graph as specified. `documentation/curation_state_machineish.js`
already documents four of them, under different state names:

| transition | count | note |
|---|---|---|
| 6 → 5 Approved → Provisional | 1,631 | re-classification after approval |
| 5 → 9 Provisional → Published | 1,377 | `approveClassification` in the older doc |
| 9 → 5 Published → Provisional | 848 | `reClassify` in the older doc |
| 7 → 5 / 7 → 6 | 97 / 61 | the graph has no exit from `Recuration assigned` except retirement |
| 2 → 1 Precuration → Uploaded | 40 | `rollback` in the older doc |
| 5 → 4 Provisional → Precuration Complete | 27 | `updateEvidence` in the older doc |

Roughly 2,750 further "not in graph" transitions are jumps from an early state to a
late one, which is the missing-history problem rather than a gap in the graph. Expect
that number to fall sharply after steps 3–5.

Until these are decided, do not wire `StatusTransitions` into validation. It is a
hypothesis under test.

**Not done, deliberately:**

- Classification history is recovered by `gci:replay` only. For the 13 curations with
  classifications and no `gdm_uuid` there is no second copy anywhere —
  Revisionable never saw classification, because it is not a column on `curations`.
- `config('curations.replaying')` is not honoured by the outgoing stream-message
  listeners (see step 3).
- The `7`/`8` positions in the tiebreak rank are inherited from their ids rather than
  chosen. Fixing that needs a decision about where `Recuration assigned` and
  `Retired Assignment` sit relative to `Published`.

## 5. Why not Venturecraft\Revisionable

Asked and answered during this work, recorded here so it is not re-litigated.
Revisionable cannot be the source of truth for field history:

1. It stamps its own `created_at` — hardcoded in four places in `RevisionableTrait` —
   so there is nowhere to put an event date.
2. It observes rather than drives: rows are diffed from `getDirty()` after the model is
   already written, so a back-dated revision cannot make current state recompute.
3. It never sees pivot writes, which is where all three histories live.
4. No uniqueness, so replays simply append.
5. `getSystemUserId()` returns null on every queue and console path — every GCI path.

It stays exactly as it is: a passive attribute-level audit trail. Its one read path,
the Backpack revise UI on ExpertPanel, User and Affiliation, went away with Backpack,
so it is now write-only -- whether to keep it at all is an open question. It earned its
keep here as the only surviving witness to the lost status transitions in step 4.

`activity_log` is unsuitable for a different reason: `config/activitylog.php` prunes it
at 365 days, and a prunable table cannot back a projection.
