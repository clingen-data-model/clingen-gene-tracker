# ClinGen Gene Tracker

Laravel 11 / PHP 8.2. The SPA lives in `resources/assets/js` (not `resources/js`).
Models are in `app/` directly (`App\Curation`, not `App\Models\Curation`).

Development runs in containers. This machine has podman rather than docker:

```
podman exec gt-app php artisan test
podman exec gt-app php artisan migrate
./dcdb                      # mysql shell on the dev database
```

The test suite uses a **separate `testing` database** with `DatabaseTransactions`,
not `RefreshDatabase`. Migrations do not run automatically — after adding one:

```
podman exec gt-app php artisan migrate --database=testing
```

## Curation field history

Status, classification and expert-panel ownership are tracked as dated history in
three pivot tables. They are the source of truth; `curations.curation_status_id`
and `curations.expert_panel_id` are derived caches, and
`curation_expert_panel.end_date` is derived too.

**All writes go through `App\Actions\Curations\RecordCurationFieldEvent`.**
`AddStatus`, `AddClassification` and `SetOwner` are thin wrappers over it and exist
only because callers already knew those names. Per-field policy — which table, which
columns, date granularity, how same-date ties break — lives in
`App\Curations\CurationField`, not scattered across the writers.

### Idempotency has two layers

1. **Hard, enforced by the database.** Every history row carries a
   `source_event_key` identifying the event that produced it, unique per
   `(curation_id, source_event_key)`. The same source event cannot be recorded
   twice, including under concurrency. For GCI messages the key is
   `"{report_id}-{date}"` — byte-identical to `incoming_stream_messages.key`, and
   recomputable from the message alone via `GciMessage::getSourceKey()`.
2. **Soft, in PHP.** An event asserting the value the timeline already holds at
   that point is not recorded. History reads as a list of transitions.

When adding a writer, give it a deterministic source key. Do not invent a random
one, and do not write to the pivots directly — the columns are `NOT NULL`
specifically so that a direct `attach()` fails loudly.

### Side effects fire on projection changes, never on ingestion

`App\Actions\Curations\ProjectCurationField` recomputes the derived data and
**compares before writing**. When nothing changes, Eloquent issues no UPDATE, no
`Curation\Updated` fires, and no outgoing stream message is produced. This is the
entire mechanism by which replaying an applied message stays silent — do not
"refresh" a model by writing identical values back.

Notifications hang off `Curation\CurrentOwnerChanged`, which the projector only
dispatches when the current owner actually moves. Do not send mail from a job that
records an event.

### Out-of-order events

An event dated before the newest known event is recorded in history but does not
displace the current value. Replay order therefore does not affect the end state,
and `gci:replay` orders by message date rather than arrival order.

`affiliation_id`, `moi_id` and `mondo_id` keep no history, so they are guarded by
`curations.gci_event_watermark` — the newest message date the curation has seen.

### Repairing drift

```
podman exec gt-app php artisan curations:rebuild-projections --dry-run
```

Recomputes every derived value from history and reports what would change. This
replaced `curations:order-statuses`, `curations:set_current_status_id` and
`curations:clean-statuses`; add coverage here rather than writing another
per-symptom repair command.

If a curation's stored status has no history row to account for it — the pointer
says Published while the only row is the Uploaded one from creation — repair the
history first, or the rebuild will demote it:

```
podman exec gt-app php artisan curations:backfill-status-history-from-revisions --dry-run
```

Revisionable logged every `curation_status_id` change, so those transitions are
recoverable even when the history rows are not. The command only touches curations
whose stored status their history cannot account for; the great majority of the
13k status revisions correspond to changes that did write history, and replaying
those would add rows dated when the pointer was written rather than when the
status changed.

**Same-day ties matter.** Status dates are truncated to the day, so a curation that
moves twice in one day has rows the date cannot separate. Ties break on
`curation_status_id`, which doubles as a workflow rank — the furthest-along status
stands. Breaking them on insertion order instead would change the current status of
99 curations, 78 of them away from Published.

Curations whose history never recorded the initial Uploaded row are repaired by:

```
podman exec gt-app php artisan curations:impute-uploaded-status --dry-run
```

The date is the earliest moment the curation can be evidenced to have existed:
`created_at`, or the first recorded status where that predates it. About a third
of these curations carry a status dated before the tracker record was made
(curated in the GCI before being uploaded here), so `created_at` alone is not a
safe floor.

History stores full timestamps. Midnight means "time unknown", not midnight —
a manually entered date has no time and never did. Legacy rows truncated to the
day are recovered by:

```
podman exec gt-app php artisan curations:restore-status-timestamps --dry-run
```

Two rules matter there. Take the time from a GCI message's **emission** date, not
its `status.date`: GCI fills `status.date` for `approved` messages with a
synthetic `16:00:00Z` that sorts after the publish that really followed it. And
apply times a whole day at a time — timing some rows of a day and not others is
worse than timing none, because the untimed ones sort to the front of that day
regardless of when they happened.

## The status state machine

`App\Curations\StatusTransitions` records which status may follow which. Nothing
enforces it — statuses have always been written without any such check — so it
exists to be measured against:

```
podman exec gt-app php artisan curations:audit-status-transitions
podman exec gt-app php artisan curations:audit-status-transitions <curation>
```

A transition outside the graph means either the graph is incomplete or the steps
between were never recorded; the two are indistinguishable from the history alone.
Roughly 42% of recorded transitions currently fall outside it, so treat the graph
as a hypothesis under test rather than a rule, and do not wire it into validation
without deciding which of the common unlisted transitions (6->5, 5->9, 9->5, 7->5)
are legitimate.

## Revisionable is not field history

`venturecraft/revisionable` is a passive attribute-level audit trail, and the
backing store for the Backpack revise UI on ExpertPanel, User and Affiliation. It
cannot express curation field history: it stamps its own `created_at` rather than
an event date, never sees pivot writes, has no idempotency key, and records a null
user on every queue and console path. Do not reach for it to add history to a field.

`activity_log` (spatie) is pruned at 365 days, so it cannot back a projection
either.
