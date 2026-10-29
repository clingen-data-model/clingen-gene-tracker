# GT / GCI reconciliation admin workflow (CGSP-1042)

## Delivered workflow

Open `/#/admin/gci-reconciliation` (Administration > GCI Reconciliation).

1. Create a labeled run, then use the same upload control for the two explicitly selected source types.
2. Reconcile after both uploads exist. HAS_PRECURATION_ID is processed before NO_PRECURATION_ID, sharing reservation/conflict tracking. Reconciliation only writes findings/run metadata; it never mutates GT curations.
3. Filter and select records. The default Needs Review queue excludes retired assignments. Include retired exposes their preserved findings. Quick queues cover not found, ID/UUID mismatch, ambiguous/conflict, deleted, safe updates, and export candidates. Additional filters cover source, match method/status, affiliation, gene, issue code, comparison flags, review, resolution and execution.
4. Open a row to inspect GCI and the immutable GT snapshot, comparison flags, deleted IDs, retirement, candidates, notes, decision, and audit history. Same-gene/parent-affiliation candidates are investigation hints only.
5. Save a decision for the explicitly selected rows. Review fields and `resolved_gt_curation_id` do not replace the original automated match. Review is audited, including changes to notes and manual targets.
6. Preview selected reviewed decisions, inspect per-field before/after changes and update/create counts, then Confirm Apply. Mapping errors appear as excluded records in the preview. Only the displayed ready records execute.
7. Download eligible selected records as GCI JSON. Export generation is tracked independently from any actual GCI import.

## Authorization and routes

All API endpoints require `auth:api` and the existing `admin|programmer` role middleware. Each operation also requires its own `gci-reconciliation` permission. RolesAndPermissionsSeeder creates and grants list/upload/review/execute/export permissions to admin and programmer; deployments already rerun this seeder. Execution remains independently protected even when roles initially have all five permissions.

Prefix: `/api/admin/gci-reconciliation`

| Method | Path | Permission |
| --- | --- | --- |
| GET | runs | list |
| POST | runs | upload |
| POST | runs/{uuid}/uploads | upload |
| POST | runs/{uuid}/reconcile | upload |
| GET | records | list |
| GET | records/{id} | list |
| POST | review | review |
| POST | preview | execute |
| POST | execute | execute |
| POST | export | export |

Review, preview and export accept explicit `ids` (maximum 500). Execute accepts only the actor-bound preview UUID, never a filter or a replacement list of IDs. The Vue table selects visible rows and clears selection when changing pages or applying filters.

## Database changes

The supplied, originally untracked `2026_07_29_000000_create_gci_gt_reconciliations_table.php` is retained. A separate incremental migration adds:

- `gci_reconciliation_runs`: UUID, label, status, uploading/starting actor, start/completion times, error, timestamps.
- `gci_reconciliation_uploads`: run/source, original filename, private path, SHA-256, actual record count, uploader, source envelope metadata, timestamps; unique run/source constraint.
- `gci_reconciliation_audits`: row, kind, action, actor, source/source ID/source event key, effective time, before/after values, failure text.
- `gci_reconciliation_previews`: UUID, actor, exact plans, expiry and consumption time.
- Additive row fields: retired flag, deleted GT IDs, resolved GT curation ID, reviewer/time, execution status/actor/time/error, exporter/time.

Existing findings are retained. Historical run UUIDs are backfilled into completed runs and retired flags are backfilled from the existing configured status. Historical rows have no invented uploader or file metadata. New CLI runs are also registered for the UI. No curation/history-pivot migrations were copied from unified-history, and no curation columns were added.

Models live under the reconciliation namespace, so the application's generic App-model API fallback cannot expose private run/upload/audit data.

## Upload and reconciliation contracts

Uploads are stored under `storage/app/gci-reconciliation/{run}/inputs/` using generated filenames. Original filenames, SHA-256, record count and actor are retained. File selection is explicit, never inferred from its filename. Backend upload size limit is 20 MiB; PHP/web-server request limits may be lower.

Validation rejects malformed JSON, non-array/missing Items, missing/invalid required fields, invalid PK UUIDs and affiliation IDs, invalid/out-of-range precuration IDs, and a with-ID file selected as without-ID. Object key order is irrelevant. An empty Items array is valid, allowing a run with no records of one source type. Missing MONDO/HP mappings are not guessed; valid free-text source fields can be reconciled but cannot be executed without safe mappings.

A repeated identical upload for the same run/source is idempotent by SHA-256. A different file for that slot is rejected: create another run. Files cannot be replaced after a run starts. Stored hashes are verified before reconciliation.

The CLI `gci:reconcile-curations` delegates matching to Reconciler and retains its original summary/CSV investigation reports. Those reports are separate from the new GCI JSON export.

Matching preserves precuration-first identity and UUID fallback. Without-ID records match only on UUID. Deleted records are excluded from matching but recorded as investigation IDs. Retired assignments use `config('curations.statuses.retired-assignment')`. Duplicate GCI PKs flag every affected occurrence, including the first, and cannot enter the safe-update group. UUID duplication/conflicts remain visible.

## Review and execution

Review statuses: PENDING, REVIEWED, NEEDS_FURTHER_REVIEW. Supported machine resolutions are centralized in Decision:

- UPDATE_GT_DISEASE_MOI
- UPDATE_GT_GDM_UUID
- MATCH_EXISTING_GT_CURATION
- CREATE_GT_CURATION
- UPDATE_GCI_PRECURATION_ID
- NO_ACTION / HISTORICAL_NO_ACTION / KEEP_DELETED
- NEEDS_FURTHER_REVIEW

Reviewed actionable decisions become PENDING; no-action decisions become NO_ACTION. Incomplete reviews remain NOT_READY. Applied decisions cannot be edited/re-executed. Invalid resolution/source/target combinations are rejected atomically for the selected review batch.

GciReconciliationExecutor is the sole GT mutation boundary. It revalidates decisions, live identity, mapping uniqueness and active targets. Previews show live changes and expire after 15 minutes. Apply consumes the preview once and compares canonical JSON plans, ignoring JSON object key ordering. A changed review, target, mapping or live GT value requires another preview.

Each record executes in a separate database transaction. The reconciliation row, run coordination rows and relevant curation/UUID range are locked. Failures roll back that record, then record FAILED and an audit entry; other selected ready records continue. Already APPLIED records cannot be applied again, including from an older second preview. Mapping failures discovered at preview are displayed without changing GT; failures discovered at execution are recorded as FAILED.

Disease/MoI updates require a clean HAS_PRECURATION_ID match by precuration ID, matching UUID, gene and parent affiliation, no identity warning and an observed disease/MoI difference. Only mondo_id and moi_id are changed. Both mappings must resolve even when only one value differs. Obsolete/missing/ambiguous diseases and missing/ambiguous HP mappings fail safely. Gene and expert-panel identity is checked after model listeners run, so an unexpected identity mutation rolls back.

UUID correction requires an explicit reviewed precuration-ID match, a mismatch and a preview. Another active or deleted GT record using the proposed UUID blocks correction.

Creation requires an approved unmatched NO_PRECURATION_ID record; an existing active/deleted UUID blocks it. Gene must resolve to one local HGNC record, MONDO to one non-obsolete disease, HP to one MoI, and parent affiliation to exactly one expert panel. Creation sets the resolved parent affiliation ID and expert panel. It uses Curation model creation and synchronous AddStatus with the existing configured Uploaded status and now(). The existing bulk-upload flag temporarily suppresses default asynchronous creation hooks; initial owner history is attached explicitly within the transaction. The flag is restored in a finally block. Existing curation events/revisions remain active. There is no automatic DX replay during reconciliation execution.

Manual matching/export resolutions record a resolved target without modifying GT identity. Retired/archived/deleted targets cannot receive actionable execution. Historical/no-action resolutions remain available. Automated comparison flags and original gt_curation_id are never rewritten by execution.

## JSON export

Eligible without-ID rows include clean direct UUID matches, explicitly reviewed/applied manual matches, approved exports, and successful creations. Export rechecks live target existence, archived/retired state, and direct-match identity/UUID uniqueness. Unresolved, failed, no-action and unresolved-conflict records are skipped. Export fails clearly if no eligible selected rows remain or duplicate source UUIDs were selected across runs.

The envelope is `Items`, `Count`, `ScannedCount`, `ConsumedCapacity`. Each item contains the original gene, modeInheritance, affiliation, disease and PK plus string `precurationId`. Original MONDO underscore notation and MoI text are preserved. Count and ScannedCount describe the generated subset; ConsumedCapacity is null. No source DynamoDB scan totals are misrepresented as export totals. An export audit and exported_at/exported_by are recorded; there is no claim that GCI imported the file.

## unified-history comparison and integration points

Read-only comparison used `main` (33362a8eaf24709dc744db5824c134c18d8a3d0e) and local remote-tracking `origin/unified-history` (02f5503d0b9d7af234eec3f803d59be6fc876e6a). The branch was not checked out, merged, cherry-picked or changed. No remote fetch was performed.

The branch introduces:

- CurationField definitions for status, classification and expert-panel ownership, mapped onto existing pivot history tables.
- RecordCurationFieldEvent as the centralized history write path, with source/source_event_key idempotency and ProjectCurationField projecting current state.
- Extended AddStatus/AddClassification/SetOwner contracts carrying source and event keys; full timestamps replace date-only history. Ownership intervals are projected and notifications follow current-owner changes.
- A gci_event_watermark on curations, protecting scalar disease/MoI/affiliation from stale DX replay.
- Replay ordered by source event date and message ID, with a replay flag and source identities derived from GCI messages.

Alignment here is intentional: one execution boundary; explicit source, source_id (GCI UUID), source_event_key and effective_at in independent audit records; synchronous existing status job usage; stable row/run-based execution identity; original observations separated from decisions and resulting changes. No unfinished upstream table definitions or scalar watermark changes are duplicated.

After the branches merge, adapt the executor's initial owner/status history operations to the final SetOwner/AddStatus or RecordCurationFieldEvent APIs, passing the reconciliation source and stable event key. Initial ownership currently uses the existing pivot because current SetOwner does not reliably record initial ownership when the panel is already assigned; adopting upstream's projector is the isolated integration point. Review Curation creation-hook changes at the same time to avoid duplicate status/ownership assertions.

The supplied JSON has no source event timestamp. effective_at records the GT execution time, not a fabricated GCI timestamp. Do not advance gci_event_watermark from upload time. Agree on snapshot timestamps/precedence with the upstream author before integrating reconciliation with watermark/replay logic. Disease/MoI/UUID changes stay in reconciliation audit because upstream's CurationField currently tracks only status/classification/ownership.

## Deployment and operational assumptions

Run the normal migrations and RolesAndPermissionsSeeder, then build frontend assets. Development data was not reset or reconciled during implementation. New migrations were applied only to the verified separate `genetracker-test` database, and the existing E2E preparation reset only `genetracker_e2e`.

Runs execute synchronously; a process killed while RUNNING requires investigation/recovery rather than an automatic unsafe restart. A normal caught failure is retryable and its transaction leaves no partial findings. Private uploads/audit/previews currently have no automatic retention pruning. Run dropdown loads the most recent 200 runs; record endpoints can address historical UUIDs directly.

Model jobs/events retain existing application behavior. Before deploying with a non-default external asynchronous queue, verify its existing transactional/after-commit delivery settings; database atomicity does not undo external work that an independently configured queue dispatches before commit.

No commits or deployment were performed. Generated frontend artifacts are verification output, not source changes.

## Files

- Existing untracked command: app/Console/Commands/Dev/ReconcileGciCurations.php (now uses shared matcher).
- Existing untracked base migration: database/migrations/2026_07_29_000000_create_gci_gt_reconciliations_table.php (retained).
- New workflow migration: database/migrations/2026_10_29_000000_add_gci_reconciliation_workflow.php.
- Backend: app/Services/GciReconciliation/{Decision,GciReconciliationExecutor,PayloadReader,Reconciler,Record,Run,Upload,Workflow}.php.
- Controller: app/Http/Controllers/Api/GciReconciliationController.php.
- Wiring: routes/api.php; database/seeds/RolesAndPermissionsSeeder.php.
- Frontend: resources/assets/js/components/admin/{GciReconciliation,GciUpload}.vue; Administration.vue; resources/assets/js/routing.js.
- Tests: tests/Feature/GciReconciliationTest.php; tests/Unit/GciReconciliation/PayloadReaderTest.php; tests/frontend/components/GciUpload.spec.js; updated AdminPassThree.spec.js navigation expectation; tests/e2e/gci-reconciliation.spec.js.
- This report: documentation/gci-reconciliation.md.

## Verification

- New PHP workflow/validation tests: 24 passed, 103 assertions.
- Existing curation PHPUnit group: 77 passed, 113 assertions.
- Frontend Vitest suite: 172 passed across 30 files.
- Vite production build passed.
- PHP syntax checks and all ten API route registrations passed.
- Existing Chromium suite: 36 passed, one email-preview test failed because Chromium reported blocked script execution inside the sandboxed about:srcdoc email frame. The same failure was reproduced using the original HEAD frontend build; email-preview code was not modified by this feature.
- New Chromium workflow test: passed end-to-end upload, reconciliation, manual review, preview, apply and JSON download (3 passed including both real-login setup tests).
