# One-time GT / GCI reconciliation (CGSP-1042)

## Persistence and deployment

Only `gci_gt_reconciliations` is required. Its original create migration
`2026_07_29_000000_create_gci_gt_reconciliations_table.php` includes the immutable
source/GCI/GT findings, retired/deleted information, review fields, resolved GT ID,
execution status/by/at/error, and export timestamp. Run UUID and optional label
remain provenance on each row; there is no run model or workflow table.

The branch-only `2026_10_29_000000_add_gci_reconciliation_workflow.php` migration
has been removed, along with the run/upload/preview/audit services. Production
should apply the single final create migration. No production drop migration is
needed, since these temporary tables have never been deployed there.

Existing local/demo databases are not modified by this refactor. If both old
migrations were already applied, the retained columns already exist. Back up any
findings you want to keep; an operator can manually remove the unused
`gci_reconciliation_runs`, `gci_reconciliation_uploads`,
`gci_reconciliation_audits`, and `gci_reconciliation_previews` tables, and their
obsolete workflow migration ledger entry. Keep `gci_gt_reconciliations` and its
original migration ledger entry. Do not roll back the removed migration.
Alternatively, reset/refresh a disposable development database with the final
migrations. This destroys its data and is not necessary for databases already
containing the retained columns. If only the original migration was previously
applied, compare/add the retained columns manually after backup, or refresh a
disposable database; editing an applied migration does not rerun it.

Re-run `RolesAndPermissionsSeeder` during normal deployment. Only list/review
reconciliation permissions remain; obsolete upload/execute/export permissions
are removed. The API also requires the existing admin/programmer role.

## Operator workflow

Copy the two GCI source files to the server and run:

```sh
php artisan gci:reconcile-curations /path/gdmHasPreCurId.json /path/gdmNoPreCurId.json
php artisan gci:apply-safe-updates
php artisan gci:apply-safe-updates --apply --user=123
php artisan gci:export-precuration-ids --output=/private/path/resolved.json
```

Reconciliation uses the existing shared matcher unchanged: direct precuration ID
first, UUID fallback, HAS before NO, and the existing duplicate/conflict rules.
JSON property ordering is irrelevant. Reconciliation records findings without
changing GT. `--label` is optional. Existing summary/report exports remain.

Apply and export accept `--run=<uuid>` and repeatable `--id=<row-id>`. If rows from
multiple reconciliation runs exist, select one explicitly to avoid mixing stale
snapshots. No browser run-management screen is required.

## Safe disease/MoI corrections

Default apply-command behavior is a read-only dry-run. It reports selected HAS,
selected NO, total, disease-only, MoI-only, both, exclusions, and mapping problems.
Counts come from eligible rows, never hardcoded production expectations. Normal
Artisan resource-usage instrumentation may still record the command itself.

HAS eligibility requires confirmed direct PRECURATION_ID matching with matching
precuration ID, UUID, gene and parent affiliation. NO eligibility requires a
confirmed unique GDM_UUID match with matching UUID, gene and parent affiliation.
Both require an original disease/MoI discrepancy, no identity warning, duplicate,
conflict, deleted/archived/retired target, or unresolved identity issue. Explicit
no-action/manual-identity/further-review decisions exclude automatic updates.
Previously APPLIED rows cannot apply again.

Each apply transaction locks the row and target, checks live identity/UUID
uniqueness again (including deleted UUID owners), and validates a unique active
MONDO and MoI mapping. It changes only `curations.mondo_id` and/or
`curations.moi_id`; gene, UUID, affiliation, Expert Panel and status are protected.
No automatic affiliation/ownership/UUID correction or curation creation exists.
Missing mappings produce a row failure and other eligible records continue.
Execution status/by/at/error is stored on the reconciliation row; snapshot fields
are never rewritten. A dry-run is not an execution token: apply always rechecks.

The standard edit controller and CLI share `Services/Curations/UpdateCuration`.
It calls Eloquent `update()` with normal listeners active, preserving revisions,
model events and DX behavior. The existing Updated listener creates a precuration
stream message; the separate GT-to-GCI Saved listener skips already linked GDMs.
No special silent update or competing audit/history table is introduced.
`--user` attributes normal revisions and execution to an existing GT user; omit
it for a system execution. External DX delivery follows existing application
behavior, including its existing transaction/queue timing.

## Admin information and review

Visit `/home#/admin/gci-reconciliation`. The browser can list, inspect, filter and
save individual/bulk review decisions and notes. It cannot upload, reconcile,
preview, execute GT changes or export JSON. API routes are limited to GET records,
GET records/{id}, and POST review, protected by list/review permissions.

`GT / Precuration ID` searches GCI precuration ID, original GT ID and resolved GT
ID. `GCI / GDM UUID` searches GCI and original GT UUID. Other filters cover source,
match method/status, gene, affiliation, issues, review, resolution, comparisons,
deleted and retired findings. Affiliation mismatches have a dedicated category.
Candidate hints remain informational; review never creates or edits a curation.
Manual match/create review requires an existing resolved ID entered after work
through normal GT screens.

Each response loads the resolved target (or original GT target), including its
current parent affiliation, Expert Panel, disease, MoI, UUID and status. Detail
shows GCI snapshot, GT at reconciliation, current GT and current comparison flags.
Current identity requires an active target, unique UUID, gene/affiliation agreement
and precuration agreement for HAS. Normal GT edits change this live comparison
on refresh, without modifying stored findings or adding synchronization listeners.
Filters describe historical findings even when the live discrepancy is fixed.

## Precuration-ID export

Clean NO records show machine-readable `gci_action=UPDATE_GCI_PRECURATION_ID`,
even when GT already matches. Safe NO corrections therefore have two separate
tasks: correct GT disease/MoI and provide GCI its precuration ID.

The export command accepts clean unique UUID matches or explicitly reviewed
manual existing/later-created targets. It rechecks active GT identity and excludes
unresolved, failed, deleted, ambiguous or conflicting records. A reviewed target
with no UUID may export only when the source UUID has no other GT owner; the
review decision establishes that manual identity. Export does not assign UUIDs.

Output preserves original raw `gene`, `modeInheritance`, `affiliation`, `disease`
and `PK`, adding string `precurationId`. Envelope fields are `Items`, `Count`,
`ScannedCount`, and `ConsumedCapacity`, compatible with the source export. The
command reports count/path and marks exported_at; it never overwrites existing
files. Default output is private storage. Generating a file does not confirm its
import into GCI. Disease/MoI corrections and ID export are independent actions.

## History compatibility

No unified-history branches or migrations are merged/copied. Actual GT changes
use the ordinary update path, so future history listeners integrate there.
Ownership discrepancies are informational/manual follow-up only: neither current
ownership fields nor ownership-history tables are changed by these commands.

## Validation of this refactor

- Reconciliation and payload tests: 19 passed, 193 assertions, including a fresh
  in-memory schema check, rollback, live comparison, searches and CLI/export rules.
- Existing curation group: 77 passed; transfer: 5 passed; ownership: 1 passed.
- Frontend unit suite: 30 files / 171 tests passed; the additional live comparison
  component test subsequently passed with both reconciliation component tests.
- Vite production build passed using a private storage output directory.
- The obsolete upload/apply Playwright scenario was replaced with an information
  page/search scenario; Playwright was not run in this validation pass.
- No normal local/demo database reset or workflow-table cleanup was performed.
