<template>
    <div>
        <h2>GCI reconciliation</h2>
        <p>Investigate the original findings alongside current GT values. Make curation corrections through the normal GT editor; reviews here record decisions only.</p>
        <div v-if="error" role="alert" class="alert alert-danger">{{ error }}</div>
        <div v-if="message" role="status" class="alert alert-info">{{ message }}</div>
        <form class="mb-3" @submit.prevent="refresh">
            <div class="row g-2">
                <div class="col-md-3"><label for="gt-id" class="form-label">GT / Precuration ID</label><input id="gt-id" v-model="filters.curation_id" type="number" min="1" class="form-control" /></div>
                <div class="col-md-5"><label for="gdm-uuid" class="form-label">GCI / GDM UUID</label><input id="gdm-uuid" v-model="filters.gdm_uuid" class="form-control" /></div>
                <div class="col-md-4"><label for="gene" class="form-label">Gene</label><input id="gene" v-model="filters.gci_gene_symbol" class="form-control" /></div>
                <div class="col-md-4"><label for="affiliation" class="form-label">GCEP / Affiliation</label><input id="affiliation" v-model="filters.gci_affiliation_id" type="number" class="form-control" /></div>
                <div class="col-md-8"><label for="queue" class="form-label">Finding category</label><select id="queue" v-model="filters.queue" class="form-select"><option value="">All records</option><option value="review">Needs Review</option><option value="not_found">Not Found in GT</option><option value="uuid">Precuration ID / GDM UUID Mismatch</option><option value="affiliation">Affiliation / Expert Panel Mismatch</option><option value="conflict">Ambiguous / Conflict</option><option value="deleted">Deleted GT Curations</option><option value="safe">Phase 1 disease / MoI candidates</option><option value="export">GCI precuration-ID export candidates</option></select></div>
            </div>
            <details class="my-3"><summary>Additional snapshot filters</summary><div class="row g-2 mt-1">
                <div v-for="(options, key) in filterOptions" :key="key" class="col-md-4"><label :for="'filter-' + key" class="form-label">{{ readable(key) }}</label><select :id="'filter-' + key" v-model="filters[key]" class="form-select"><option value="">Any</option><option v-for="option in options" :key="option" :value="option">{{ statusLabels[option] || option }}</option></select></div>
                <div v-for="key in flags" :key="key" class="col-md-4"><label :for="'filter-' + key" class="form-label">{{ readable(key) }}</label><select :id="'filter-' + key" v-model="filters[key]" class="form-select"><option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select></div>
                <div class="col-md-4"><label for="issue" class="form-label">Issue code</label><input id="issue" v-model="filters.issue" class="form-control" /></div>
            </div></details>
            <label class="form-check mb-2"><input v-model="includeRetired" class="form-check-input" type="checkbox" /> Include retired</label>
            <button class="btn btn-primary" :disabled="busy">Search / Apply filters</button>
        </form>
        <p class="text-muted">Filters describe the original findings. Current GT comparisons are recalculated when loaded. Refresh after editing a curation.</p>
        <details class="mb-3"><summary>Export JSON</summary>
            <p>Filtered downloads include every page using the last applied table filters. Downloads do not modify GT or reconciliation records.</p>
            <div class="d-flex flex-wrap gap-2">
                <button v-for="option in exportOptions" :key="option.type + option.scope" class="btn btn-outline-primary" :disabled="busy" @click="downloadExport(option.type, option.scope)">{{ option.label }}</button>
            </div>
        </details>
        <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><input type="checkbox" aria-label="Select visible records for review" :checked="allSelected" @change="selected = $event.target.checked ? rows.map(r => r.id) : []" /></th><th>Gene / GCEP</th><th>GT ID</th><th>Original finding</th><th>Current GT</th><th>Review / Action</th><th></th></tr></thead><tbody>
            <tr v-for="row in rows" :key="row.id"><td><input v-model="selected" type="checkbox" :value="row.id" :aria-label="'Select record ' + row.id" /></td><td>{{ row.gci_gene_symbol }} / {{ row.gci_affiliation_id }}</td><td>{{ row.resolved_gt_curation_id || row.gt_curation_id || 'Unresolved' }}</td><td>{{ row.match_status }}<br /><small>{{ (row.issue_codes || []).join(', ') }}</small></td><td>{{ row.current_matches_gci ? 'Matches GCI now' : 'Discrepancy / investigation needed' }}<br /><small>{{ row.gci_action }}</small></td><td>{{ reviewLabel(row) }}</td><td><button class="btn btn-sm btn-outline-primary" :disabled="busy" @click="open(row.id)">Inspect {{ row.id }}</button></td></tr>
        </tbody></table></div>
        <p v-if="!rows.length">No records match these filters.</p>
        <div class="d-flex gap-2 mb-3"><button class="btn btn-outline-secondary" :disabled="busy || page <= 1" @click="loadPage(page - 1)">Previous</button><span>Page {{ page }} of {{ lastPage }} - {{ total }} records</span><button class="btn btn-outline-secondary" :disabled="busy || page >= lastPage" @click="loadPage(page + 1)">Next</button></div>
        <section v-if="detail" class="card mb-3"><div class="card-body">
            <h3 class="h5">Record {{ detail.record.id }}</h3>
            <p>{{ detail.record.source_type }} - {{ detail.record.source_filename }} - row {{ detail.record.source_row_number }}</p>
            <p>{{ detail.record.match_method }} / {{ detail.record.match_status }} - Run UUID: {{ detail.record.run_uuid }}</p>
            <div class="table-responsive"><table class="table"><thead><tr><th>Field</th><th>GCI snapshot</th><th>GT at reconciliation</th><th>Current GT</th><th>Current comparison</th></tr></thead><tbody><tr v-for="field in comparisons" :key="field[0]"><th>{{ field[0] }}</th><td>{{ detail.record[field[1]] ?? 'None' }}</td><td>{{ detail.record[field[2]] ?? 'None' }}</td><td>{{ detail.record.current_gt?.[field[3]] ?? 'None' }}</td><td>{{ matchLabel(detail.record.current_comparison[field[4]]) }}</td></tr></tbody></table></div>
            <p>Original GT Expert Panel: {{ detail.record.gt_expert_panel_id }} - {{ detail.record.gt_expert_panel_name }}</p>
            <p>Current GT Expert Panel: {{ detail.record.current_gt?.expert_panel_id }} - {{ detail.record.current_gt?.expert_panel_name }} / {{ detail.record.current_gt?.affiliation_name }}</p>
            <p>Current status: {{ detail.record.current_gt?.status_name || 'Missing GT record' }}; deleted: {{ detail.record.current_gt?.deleted_at || 'No' }}; archived: {{ detail.record.current_gt?.archived_at || 'No' }}</p>
            <p>Original retired: {{ detail.record.gt_is_retired ? 'Yes' : 'No' }}; deleted GT IDs: {{ (detail.record.deleted_gt_curation_ids || []).join(', ') || (detail.record.precuration_id_exists_deleted ? detail.record.gci_precuration_id : 'None') }}</p>
            <p>Original issues: {{ (detail.record.issue_codes || []).join(', ') || 'None' }}</p>
            <p v-if="detail.record.gci_action">GCI follow-up: {{ detail.record.gci_action }} (separate from GT disease/MoI corrections).</p>
            <p>Possible candidates (investigation hints only):</p><ul><li v-for="candidate in detail.candidates" :key="candidate.id"><router-link :to="'/curations/' + candidate.id">GT {{ candidate.id }}</router-link> - {{ candidate.gdm_uuid }} - {{ candidate.mondo_id }}</li></ul>
            <router-link v-if="detail.record.current_gt" class="btn btn-outline-primary me-2" :to="'/curations/' + detail.record.current_gt.curation_id">Open GT curation</router-link>
            <button class="btn btn-outline-secondary" :disabled="busy" @click="open(detail.record.id)">Refresh current GT</button>
            <p class="mt-3">Reviewed by {{ detail.record.reviewed_by || 'None' }} at {{ detail.record.reviewed_at || 'None' }}</p>
            <p>Review / Action: {{ reviewLabel(detail.record) }} - {{ detail.record.execution_error }} - {{ detail.record.executed_at }}</p>
            <p>Legacy export timestamp: {{ detail.record.exported_at || 'Never' }} (exports are now read-only; this timestamp is not updated).</p>
        </div></section>
        <form v-if="canReview" class="card" @submit.prevent="saveReview"><fieldset class="card-body" :disabled="busy || !selected.length">
            <h3 class="h5">Record review for {{ selected.length }} selected rows</h3>
            <p>These decisions do not execute GT changes.</p>
            <label for="review-status" class="form-label">Review status</label><select id="review-status" v-model="decision.review_status" class="form-select mb-2"><option v-for="status in reviewStatuses" :key="status" :value="status">{{ statusLabels[status] || status }}</option></select>
            <label for="review-resolution" class="form-label">Resolution / follow-up</label><select id="review-resolution" v-model="decision.resolution" class="form-select mb-2"><option value="">No decision</option><option v-for="resolution in resolutions" :key="resolution" :value="resolution">{{ statusLabels[resolution] || resolution }}</option></select>
            <label for="resolved-id" class="form-label">Resolved GT curation ID</label><input id="resolved-id" v-model="decision.resolved_gt_curation_id" type="number" min="1" class="form-control mb-2" />
            <label for="review-notes" class="form-label">Review notes</label><textarea id="review-notes" v-model="decision.review_notes" class="form-control mb-2" />
            <button class="btn btn-primary">Save review</button>
        </fieldset></form>
    </div>
</template>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
const store = useStore()
const canReview = computed(() => store.getters.getUser.hasPermission('review gci-reconciliation'))
const base = '/api/admin/gci-reconciliation'
const rows = ref([]), selected = ref([]), detail = ref(null), busy = ref(false), error = ref(''), message = ref(''), includeRetired = ref(false)
const appliedFilters = ref({ include_retired: 0 })
const exportOptions = [
    { type: 'precuration-ids', scope: 'all', label: 'Precuration IDs - All eligible' },
    { type: 'precuration-ids', scope: 'filtered', label: 'Precuration IDs - Filtered' },
    { type: 'actions', scope: 'all', label: 'GCI Actions - All eligible' },
    { type: 'actions', scope: 'filtered', label: 'GCI Actions - Filtered' },
]
const page = ref(1), lastPage = ref(1), total = ref(0)
const filters = reactive({ curation_id: '', gdm_uuid: '', queue: '', gci_gene_symbol: '', gci_affiliation_id: '' })
const gciActions = ['DELETE_GCI_DATA', 'MOVE_TO_PARKING_LOT']
const statusLabels = { DELETE_GCI_DATA: 'Delete GCI data', MOVE_TO_PARKING_LOT: 'Move to parking lot', PENDING: 'Pending', REVIEWED: 'Reviewed', NEEDS_FURTHER_REVIEW: 'Needs further review', APPLIED: 'Applied', NO_ACTION: 'No action', RESOLVED_MANUALLY: 'Resolved manually', NOT_READY: 'Not ready', FAILED: 'Failed' }
function reviewLabel(row) {
    if (row.review_status === 'PENDING') return 'Pending'
    const review = statusLabels[row.review_status] || row.review_status
    if (row.review_status !== 'REVIEWED') return review
    if (gciActions.includes(row.resolution)) return `${review} / ${statusLabels[row.resolution]}`
    return `${review} / ${statusLabels[row.execution_status] || row.execution_status || 'Not ready'}`
}
const reviewStatuses = ['PENDING', 'REVIEWED', 'NEEDS_FURTHER_REVIEW']
const resolutions = ['UPDATE_GT_DISEASE_MOI', 'UPDATE_GT_GDM_UUID', 'UPDATE_GT_AFFILIATION', 'MATCH_EXISTING_GT_CURATION', 'CREATE_GT_CURATION', 'UPDATE_GCI_PRECURATION_ID', 'NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED', 'NEEDS_FURTHER_REVIEW', ...gciActions]
const decision = reactive({ review_status: 'REVIEWED', resolution: '', review_notes: '', resolved_gt_curation_id: '' })
const filterOptions = { source_type: ['HAS_PRECURATION_ID', 'NO_PRECURATION_ID'], match_status: ['CONFIRMED', 'NOT_FOUND_IN_GT', 'AMBIGUOUS_GDM_UUID', 'ALREADY_RECONCILED', 'CONFLICT'], match_method: ['PRECURATION_ID', 'GDM_UUID'], review_status: reviewStatuses, resolution: resolutions, execution_status: ['NOT_READY', 'PENDING', 'APPLIED', 'FAILED', 'NO_ACTION', 'RESOLVED_MANUALLY'] }
const flags = ['has_identity_warning', 'disease_match', 'moi_match', 'gdm_uuid_match', 'gene_match', 'affiliation_match', 'gt_is_retired']
const comparisons = [['Precuration ID', 'gci_precuration_id', 'gt_curation_id', 'curation_id', 'precuration_id_match'], ['GDM UUID', 'gci_gdm_uuid', 'gt_gdm_uuid', 'gdm_uuid', 'gdm_uuid_match'], ['Gene', 'gci_gene_symbol', 'gt_gene_symbol', 'gene_symbol', 'gene_match'], ['Disease', 'gci_mondo_id', 'gt_mondo_id', 'mondo_id', 'disease_match'], ['MoI', 'gci_moi_hp_id', 'gt_moi_hp_id', 'moi_hp_id', 'moi_match'], ['Affiliation', 'gci_affiliation_id', 'gt_affiliation_id', 'affiliation_id', 'affiliation_match']]
const allSelected = computed(() => rows.value.length > 0 && rows.value.every(row => selected.value.includes(row.id)))
const readable = key => key.replaceAll('_', ' ')
const matchLabel = value => value == null ? 'Not comparable' : value ? 'Match' : 'Mismatch'
async function perform(fn) {
    busy.value = true; error.value = ''; message.value = ''
    try { await fn() } catch (e) { error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || e.message }
    finally { busy.value = false }
}
async function fetchRows(n = 1) {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    const { data } = await window.axios.get(`${base}/records`, { params: { ...params, include_retired: includeRetired.value ? 1 : 0, page: n } })
    appliedFilters.value = { ...params, include_retired: includeRetired.value ? 1 : 0 }
    rows.value = data.data; page.value = data.current_page; lastPage.value = data.last_page; total.value = data.total
}
function downloadExport(type, scope) {
    return perform(async () => {
        let response
        try {
            response = await window.axios.get(`${base}/export/${type}`, {
                params: { ...(scope === 'filtered' ? appliedFilters.value : {}), scope }, responseType: 'blob',
            })
        } catch (e) {
            if (e.response?.data instanceof Blob) {
                try { e.response.data = JSON.parse(await e.response.data.text()) } catch { /* Keep the request error if not JSON. */ }
            }
            throw e
        }
        const filename = response.headers['content-disposition']?.match(/filename="?([^";]+)"?/)?.[1] || `gci-${type}.json`
        const url = URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url; link.download = filename
        document.body.appendChild(link); link.click(); link.remove()
        // Allow the browser to start consuming the object URL before releasing it.
        setTimeout(() => URL.revokeObjectURL(url), 1000)
        message.value = `Downloaded ${response.headers['x-export-count'] || ''} records. Skipped ${response.headers['x-export-skipped'] || 0} ineligible records.`
    })
}
function refresh() { selected.value = []; return perform(() => fetchRows()) }
function loadPage(n) { selected.value = []; return perform(() => fetchRows(n)) }
function open(id) { return perform(async () => { detail.value = (await window.axios.get(`${base}/records/${id}`)).data; selected.value = [id]; const r = detail.value.record; Object.assign(decision, { review_status: r.review_status, resolution: r.resolution || '', review_notes: r.review_notes || '', resolved_gt_curation_id: r.resolved_gt_curation_id || '' }) }) }
function saveReview() { return perform(async () => { await window.axios.post(`${base}/review`, { ids: selected.value, ...decision, resolution: decision.resolution || null, resolved_gt_curation_id: decision.resolved_gt_curation_id || null }); await fetchRows(page.value); if (detail.value) detail.value = (await window.axios.get(`${base}/records/${detail.value.record.id}`)).data; message.value = 'Review saved. GT curations were not modified.' }) }
onMounted(() => perform(() => fetchRows()))
</script>
