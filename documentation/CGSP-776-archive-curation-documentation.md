# CGSP-776: Archive Curation

## Summary

This ticket adds archive support for curations.

Archiving is **separate from curation status**. A curation can remain in its existing status, including `Published`, while also being archived.

The purpose of archive is to **freeze the curation** while preserving its data and status history.

---

## Business Rules

### Archive behavior
- Archive does **not** replace `curation_status_id`
- Archive is represented by `archived_at` on the `curations` table
- A curation is considered archived when `archived_at` is not null
- A curation can be both:
  - `published`
  - `archived`

### Editability
- Archived curations are read-only for normal users
- `programmer` and `admin` can still edit archived curations
- Only `programmer` and `admin` can archive / unarchive curations

### Linking archived curations
Archived curations can optionally be linked to active curations to support:
- **Lumping**: multiple archived curations linked to one active curation
- **Splitting**: one archived curation linked to multiple active curations

Link management is only done from the **active / non-archived** curation.

Archived curations only display their linked current curations in read-only mode.

---

## Database Changes

### `curations` table
Added fields:
- `archived_at` nullable timestamp
- `archive_reason` nullable text
- `gcex_url` nullable text

### `curation_archive_links` table
Created pivot table:
- `id`
- `curation_id`
- `archived_curation_id`
- timestamps

Meaning:
- `curation_id` = active / current curation
- `archived_curation_id` = archived curation

This supports both lumping and splitting without storing a separate type field.

---

## Model Changes

### `Curation` model
Added:
- `archived_at` cast to `datetime`
- `is_archived` accessor and appended attribute
- relationships for linked archived/current curations

Relationships:
- `linkedArchivedCurations()`
- `linkedCurrentCurations()`

---

## Authorization / Policy Changes

Updated `CurationPolicy` so that:
- normal users cannot update archived curations
- `programmer` and `admin` can still update archived curations
- `programmer` and `admin` can archive and unarchive curations

The project already has privileged-role logic for:
- `programmer`
- `admin`

That same privileged-role concept is used for archive behavior.

---

## Backend Changes

### Archive / unarchive endpoints
Added endpoints to archive and unarchive a curation.

Archive saves:
- `archived_at`
- `archive_reason`
- `gcex_url`

Unarchive clears:
- `archived_at`
- `archive_reason`
- `gcex_url`

### Main save/update protection
The edit flow was updated so archived curations cannot be saved by non-privileged users.

### Archived curation linking
Archived-curation linking is saved as part of the **normal curation create/update flow**, not through a separate link-save endpoint.

Request validation added on both create and update:
- `archived_curation_ids` => nullable array
- `archived_curation_ids.*` => integer, exists in `curations`

Link syncing is handled after create/update by syncing only valid archived curations to the pivot table.

### Search endpoint for archived curations
A search endpoint returns archived curations for the UI picker.

This was simplified to a generic archived-curation search so it can also be used during new curation creation.

---

## Frontend Changes

### Info tab
Archive management was added to the **Info** tab.

Behavior:
- archive state is shown with a warning/banner
- archive reason and GCEx URL are displayed when archived
- archive controls are available only to `programmer` / `admin`
- non-privileged users see a message telling them to contact an administrator
- archive / unarchive responses are merged back into the local curation object so the form keeps the same data shape after archiving or unarchiving

### Archive warning for GT → GCI sync
The archive form can show an informational warning when the record can be archived in GeneTracker but **cannot** be sent to GCI as an archive update.

The warning is shown when the curation has:
- no linked `gdm_uuid`
- and not enough identifying data (`hgnc_id`, `mondo_id`, and `moi_id`)

This is a warning only. It does **not** block archiving in GeneTracker.

Recommended warning text:

> This curation can still be archived in GeneTracker, but the archive update will not be sent to GCI because the record does not currently have a linked GCI UUID or enough identifying data (gene, disease, and mode of inheritance).

### Edit form behavior
When a curation is archived:
- editable fields are disabled for non-privileged users
- save/update actions are hidden for non-privileged users
- auto-save is blocked for non-privileged users
- transfer/delete related controls were updated to respect archive rules

### Archived curation links UI
Created a dedicated Vue component:
- `ArchivedCurationLinks.vue`

Used in:
- `Info.vue` for editable behavior on active curations
- `Show.vue` for read-only display

Behavior:
- active curation: can search/select/remove archived curations
- archived curation: shows linked current curations only
- new curation: can select archived curations before first save; selected IDs are submitted with the normal create request

### Visual indicators
Archived badge / label added to:
- curation list
- bulk lookup status column
- archived banner in edit view

---

## DX Message Changes

### `gt-precuration-events`
The outbound precuration DX payload is built in:
- `App\DataExchange\MessageFactories\PrecurationV1MessageFactory`

Archive-related fields were added to the precuration payload:
- `is_archived`
- `archived_at`
- `archive_reason`
- `gcex_url`

Because the payload uses `array_filter(...)`, `null` values are omitted from the final DX message.

### `gt-gci`
GeneTracker also has a separate GT → GCI sync path.

This flow is triggered from:
- `App\Events\Curation\Saved`
- `App\Listeners\Curations\MakeGtGciSyncMessage`

This is separate from the general `gt-precuration-events` flow, which still runs on normal `Created` / `Updated` / `Deleted` curation events.

That means an archive change can:
- still send a regular update to `gt-precuration-events`
- also send a message to `gt-gci` when the GT → GCI rules allow it

### Archive-specific GT → GCI events
For GT → GCI, archive changes are treated as their own event types:
- `curation_archived`
- `curation_unarchived`

These event types make archive/unarchive easier to search and distinguish from:
- `precuration_completed`
- `gdm_updated`

### GT → GCI archive/unarchive conditions
Archive/unarchive uses looser logic than the older precuration-complete sync rules.

For archive-related GT → GCI messages:
- `precuration complete` status is **not** required
- a linked `gdm_uuid` is allowed
- if the curation does **not** have `gdm_uuid`, it must have enough identifying data:
  - gene (`hgnc_id`)
  - disease (`mondo_id`)
  - mode of inheritance (`moi_id`)

If the curation has neither:
- linked `gdm_uuid`
- nor enough identifying data

then the archive/unarchive message is **not** sent to `gt-gci`.

This matches the UI warning described above.

---

## GT → DX Flow Summary

### General precuration DX flow
1. A curation is created, updated, or deleted
2. Curation event listeners create a stream message job
3. `CreateStreamMessage` uses `MessageFactoryInterface`
4. `MessageFactoryInterface` resolves to `PrecurationV1MessageFactory`
5. A stream message is stored and later pushed to the configured topic

This path sends to:
- `gt-precuration-events`

### GT → GCI sync flow
1. A curation is saved
2. `MakeGtGciSyncMessage` runs
3. The listener checks whether the curation qualifies for GT → GCI sync
4. If it qualifies, it dispatches `CreateStreamMessage` with topic:
   - `gt-gci`

This path is used for:
- `precuration_completed`
- `gdm_updated`
- `curation_archived`
- `curation_unarchived`

---

## Linking Rules

### Lumping
Multiple archived curations linked to one active curation.

Example:
- Active A
- Archived X
- Archived Y

Rows:
- `(curation_id=A, archived_curation_id=X)`
- `(curation_id=A, archived_curation_id=Y)`

### Splitting
One archived curation linked to multiple active curations.

Example:
- Archived X
- Active A
- Active B

Rows:
- `(curation_id=A, archived_curation_id=X)`
- `(curation_id=B, archived_curation_id=X)`

---

## Expected User Experience

### Active curation
- fully editable per normal permission rules
- can manage linked archived curations
- can be archived by privileged users

### Archived curation
- keeps existing status
- shows archived badge / banner
- read-only for non-privileged users
- still editable by `programmer` / `admin`
- displays linked current curations in read-only mode

### Archive + GCI case
- users can still archive in GeneTracker even if the record cannot be sent to GCI
- when the record does not meet GT → GCI identification rules, the UI warns the user but does not block the archive action

---

## Notes

- Archive is intentionally **not** a curation status.
- Archive acts as a layer on top of status.
- The implementation keeps the existing workflow/status model intact while adding a freeze/read-only mechanism.
- `gt-precuration-events` and `gt-gci` are separate outbound flows and can both be triggered by the same archive update.
