<template>
    <div>
        <h2>GCI reconciliation</h2>
        <p>Upload both GCI exports, reconcile, review decisions, then preview and apply selected records.</p>
        <div v-if="error" role="alert" class="alert alert-danger">{{ error }}</div>
        <div v-if="message" role="status" class="alert alert-info">{{ message }}</div>
        <form v-if="can('upload')" class="d-flex gap-2 mb-3" @submit.prevent="createRun">
            <input v-model="label" class="form-control" aria-label="Optional run label" placeholder="Optional run label" maxlength="255" />
            <button class="btn btn-primary text-nowrap" :disabled="busy">Create run</button>
        </form>
        <label for="gci-run" class="form-label">Reconciliation run</label>
        <select id="gci-run" v-model="filters.run_uuid" class="form-select mb-3" @change="refresh">
            <option value="">All runs</option>
            <option v-for="run in runs" :key="run.uuid" :value="run.uuid">{{ run.label || run.uuid }} - {{ run.status }}</option>
        </select>
        <div v-if="currentRun" class="mb-3">
            <p>Run: {{ currentRun.uuid }} - {{ currentRun.status }}</p>
            <ul><li v-for="upload in currentRun.uploads" :key="upload.id">{{ upload.source_type }}: {{ upload.filename }} ({{ upload.record_count }} records)<br /><small>SHA-256: {{ upload.sha256 }}</small></li></ul>
            <p v-if="currentRun.error" class="text-danger">{{ currentRun.error }}</p>
            <GciUpload v-if="can('upload') && currentRun.status === 'DRAFT'" :disabled="busy" @upload="upload" />
            <button v-if="can('upload') && ['DRAFT', 'FAILED'].includes(currentRun.status)" class="btn btn-primary" :disabled="busy || currentRun.uploads.length !== 2" @click="reconcile">Reconcile both files</button>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label for="gci-queue" class="form-label">Review queue</label>
                <select id="gci-queue" v-model="filters.queue" class="form-select">
                    <option value="">All records</option><option value="review">Needs Review</option>
                    <option value="not_found">Not Found in GT</option><option value="uuid">Precuration ID / GDM UUID Mismatch</option>
                    <option value="affiliation">Affiliation / Expert Panel Mismatch</option><option value="conflict">Ambiguous / Conflict</option><option value="deleted">Deleted GT Curations</option>
                    <option value="safe">Safe GCI to GT Updates</option><option value="export">Needs GCI Precuration ID Export</option>
                </select>
            </div>
            <div class="col-md-3"><label for="gci-gene" class="form-label">Gene</label><input id="gci-gene" v-model="filters.gci_gene_symbol" class="form-control" /></div>
            <div class="col-md-3"><label for="gci-affiliation" class="form-label">Affiliation</label><input id="gci-affiliation" v-model="filters.gci_affiliation_id" class="form-control" type="number" /></div>
        </div>
        <details class="mb-3"><summary>Additional filters</summary><div class="row g-2 mt-1">
            <div v-for="(options, key) in filterOptions" :key="key" class="col-md-4">
                <label :for="'filter-' + key" class="form-label">{{ readable(key) }}</label>
                <select :id="'filter-' + key" v-model="filters[key]" class="form-select"><option value="">Any</option><option v-for="option in options" :key="option" :value="option">{{ option }}</option></select>
            </div>
            <div v-for="key in flags" :key="key" class="col-md-4"><label :for="'filter-' + key" class="form-label">{{ readable(key) }}</label><select :id="'filter-' + key" v-model="filters[key]" class="form-select"><option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select></div>
            <div class="col-md-4"><label for="gci-issue" class="form-label">Issue code</label><input id="gci-issue" v-model="filters.issue" class="form-control" /></div>
        </div></details>
        <label class="form-check mb-3"><input v-model="includeRetired" type="checkbox" class="form-check-input" /> Include retired</label>
        <button class="btn btn-outline-primary mb-3" :disabled="busy" @click="refresh">Apply filters</button>
        <div class="table-responsive"><table class="table table-striped align-middle">
            <thead><tr><th><input type="checkbox" aria-label="Select visible records" :checked="allSelected" @change="selectVisible($event.target.checked)" /></th><th>Gene / affiliation</th><th>GT ID</th><th>Match</th><th>Issues</th><th>Review / execution</th><th></th></tr></thead>
            <tbody><tr v-for="row in rows" :key="row.id">
                <td><input v-model="selected" type="checkbox" :value="row.id" :aria-label="'Select record ' + row.id" /></td>
                <td>{{ row.gci_gene_symbol }} / {{ row.gci_affiliation_id }}<br /><small>{{ row.source_type }}</small></td>
                <td>{{ row.gt_curation_id || 'None' }}<span v-if="row.resolved_gt_curation_id"> to {{ row.resolved_gt_curation_id }}</span></td>
                <td>{{ row.match_status }}<br /><small>{{ row.match_method }} {{ row.gt_is_retired ? 'Retired' : '' }}</small></td>
                <td>{{ (row.issue_codes || []).join(', ') }}</td>
                <td>{{ row.review_status }} / {{ row.execution_status }}</td>
                <td><button class="btn btn-sm btn-outline-primary" :disabled="busy" @click="open(row.id)">Review {{ row.id }}</button></td>
            </tr></tbody>
        </table></div>
        <p v-if="!rows.length">No records match these filters.</p>
        <div class="d-flex gap-2 mb-3"><button class="btn btn-outline-secondary" :disabled="busy || page <= 1" @click="loadPage(page - 1)">Previous</button><span>Page {{ page }} of {{ lastPage }} - {{ total }} records</span><button class="btn btn-outline-secondary" :disabled="busy || page >= lastPage" @click="loadPage(page + 1)">Next</button></div>
        <section v-if="detail" class="card mb-3"><div class="card-body">
            <h3 class="h5">Record {{ detail.record.id }}</h3>
            <p>{{ detail.record.source_type }} - {{ detail.record.match_method }} - {{ detail.record.match_status }}</p>
            <table class="table"><thead><tr><th>Field</th><th>GCI</th><th>GT snapshot</th><th>Comparison</th></tr></thead><tbody>
                <tr v-for="field in comparisons" :key="field[0]"><th>{{ field[0] }}</th><td>{{ detail.record[field[1]] || 'None' }}</td><td>{{ detail.record[field[2]] || 'None' }}</td><td :class="detail.record[field[3]] === 0 ? 'text-danger fw-bold' : ''">{{ detail.record[field[3]] == null ? 'Not comparable' : detail.record[field[3]] ? 'Match' : 'Mismatch' }}</td></tr>
            </tbody></table>
            <p>Issues: {{ (detail.record.issue_codes || []).join(', ') || 'None' }}</p>
            <p>Retired: {{ detail.record.gt_is_retired ? 'Yes' : 'No' }} - Deleted GT IDs: {{ (detail.record.deleted_gt_curation_ids || []).join(', ') || (detail.record.precuration_id_exists_deleted ? detail.record.gci_precuration_id : 'None') }}</p>
            <p>Possible GT candidates (investigation hints only):</p><ul><li v-for="candidate in detail.candidates" :key="candidate.id">GT {{ candidate.id }} - {{ candidate.gdm_uuid || 'No GDM UUID' }} - {{ candidate.mondo_id }}</li></ul>
            <div v-if="detail.current_ownership" class="border rounded p-2 mb-2">
                <p>GCI parent affiliation: {{ detail.record.gci_affiliation_id }}</p>
                <p>Current GT parent affiliation / GCEP: {{ detail.current_ownership.parent_clingen_id || 'None' }} - {{ detail.current_ownership.parent_name || 'None' }}</p>
                <p>GT stored parent affiliation: {{ detail.current_ownership.stored_parent_clingen_id || 'None' }} - {{ detail.current_ownership.stored_parent_name || 'None' }}</p>
                <p>Current GT Expert Panel: {{ detail.current_ownership.expert_panel_id }} - {{ detail.current_ownership.expert_panel_name }}</p>
            </div>
            <p>Existing decision: {{ detail.record.resolution || 'None' }} - {{ detail.record.review_notes }}</p>
            <p>Reviewed by {{ detail.record.reviewed_by || 'None' }} at {{ detail.record.reviewed_at || 'None' }}</p>
            <p>Execution: {{ detail.record.execution_status }} - {{ detail.record.execution_error }}</p>
            <details><summary>Audit history</summary><pre class="audit-history">{{ JSON.stringify(detail.history, null, 2) }}</pre></details>
            <button class="btn btn-outline-secondary mt-2" @click="detail = null">Close detail</button>
        </div></section>
        <section class="card mb-3"><div class="card-body">
            <h3 class="h5">{{ selected.length }} selected records</h3>
            <form v-if="can('review')" @submit.prevent="saveReview">
                <fieldset :disabled="busy">
                <div class="row g-2">
                    <div class="col-md-6"><label for="review-status" class="form-label">Review status</label><select id="review-status" v-model="decision.review_status" class="form-select"><option v-for="status in reviewStatuses" :key="status">{{ status }}</option></select></div>
                    <div class="col-md-6"><label for="review-resolution" class="form-label">Resolution</label><select id="review-resolution" v-model="decision.resolution" class="form-select"><option value="">Choose resolution</option><option v-for="resolution in reviewResolutions" :key="resolution" :value="resolution">{{ resolutionLabel(resolution) }}</option></select></div>
                    <div class="col-md-6"><label for="resolved-id" class="form-label">Resolved GT curation ID (manual match)</label><input id="resolved-id" v-model="decision.resolved_gt_curation_id" type="number" min="1" class="form-control" /></div>
                    <div class="col-12"><label for="review-notes" class="form-label">Review notes</label><textarea id="review-notes" v-model="decision.review_notes" class="form-control" /></div>
                </div>
                <button class="btn btn-primary my-3" :disabled="busy || !selected.length">Record decision for selected rows</button>
                </fieldset>
            </form>
            <div class="d-flex gap-2"><button v-if="can('execute')" class="btn btn-outline-primary" :disabled="busy || !selected.length" @click="previewSelected">Preview selected decisions</button><button v-if="can('export')" class="btn btn-outline-secondary" :disabled="busy || !selected.length" @click="exportSelected">Download eligible selected GCI records</button></div>
            <p class="text-muted mt-2">Generating an export does not confirm that GCI has imported it.</p>
        </div></section>
        <section v-if="preview" class="card border-primary"><div class="card-body">
            <h3 class="h5">Execution preview</h3>
            <p>{{ preview.summary.selected }} selected - {{ preview.summary.ready }} ready - Disease updates: {{ preview.summary.disease_updates }} - MoI updates: {{ preview.summary.moi_updates }} - GDM UUID updates: {{ preview.summary.uuid_updates }} - Ownership / Expert Panel changes: {{ preview.summary.ownership_updates }} - New GT curations: {{ preview.summary.new_curations }}</p>
            <p>Only the displayed fields will change. Ownership changes require an explicitly reviewed affiliation decision. Preview expires in 15 minutes.</p>
            <ul><li v-for="item in preview.errors" :key="item.id" class="text-danger">Record {{ item.id }} excluded: {{ item.error }}</li></ul>
            <div v-for="plan in preview.plans" :key="plan.id" class="border rounded p-2 mb-2"><strong>Record {{ plan.id }}: {{ plan.resolution }} - GT {{ plan.target_id || 'new curation' }}</strong><div v-if="plan.ownership" class="mt-2">
                <h4 class="h6">Ownership / Expert Panel change</h4>
                <p>Current GT parent affiliation: {{ plan.ownership.current.parent_clingen_id || 'None' }} - {{ plan.ownership.current.parent_name || 'None' }}</p>
                <p>GT stored parent affiliation: {{ plan.ownership.current.stored_parent_clingen_id || 'None' }} - {{ plan.ownership.current.stored_parent_name || 'None' }}</p>
                <p>Current GT Expert Panel: {{ plan.ownership.current.expert_panel_id }} - {{ plan.ownership.current.expert_panel_name }}</p>
                <p>GCI parent affiliation: {{ plan.ownership.gci_affiliation_id }}</p>
                <p>Resolved target parent: {{ plan.ownership.target.parent_clingen_id }} - {{ plan.ownership.target.parent_name }}</p>
                <p>Resolved target Expert Panel: {{ plan.ownership.target.expert_panel_id }} - {{ plan.ownership.target.expert_panel_name }}</p>
            </div><pre>{{ JSON.stringify(plan.changes, null, 2) }}</pre></div>
            <button class="btn btn-danger" :disabled="busy || !preview.plans.length" @click="execute">Confirm Apply {{ preview.plans.length }} previewed decisions</button>
            <button class="btn btn-outline-secondary ms-2" @click="preview = null">Cancel</button>
        </div></section>
    </div>
</template>
<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useStore } from 'vuex'
import GciUpload from './GciUpload.vue'
const store = useStore()
const can = action => store.getters.getUser.hasPermission(`${action} gci-reconciliation`)
const base = '/api/admin/gci-reconciliation'
const runs = ref([]), rows = ref([]), selected = ref([]), detail = ref(null), preview = ref(null)
const label = ref(''), busy = ref(false), error = ref(''), message = ref(''), includeRetired = ref(false)
const page = ref(1), lastPage = ref(1), total = ref(0)
const filters = reactive({ run_uuid: '', queue: 'review', gci_gene_symbol: '', gci_affiliation_id: '' })
const reviewStatuses = ['PENDING', 'REVIEWED', 'NEEDS_FURTHER_REVIEW']
const resolutions = ['UPDATE_GT_DISEASE_MOI', 'UPDATE_GT_GDM_UUID', 'UPDATE_GT_AFFILIATION', 'MATCH_EXISTING_GT_CURATION', 'CREATE_GT_CURATION', 'UPDATE_GCI_PRECURATION_ID', 'NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED', 'NEEDS_FURTHER_REVIEW']
const resolutionLabel = value => value === 'UPDATE_GT_AFFILIATION' ? 'Update GT Affiliation / Expert Panel' : value
const reviewResolutions = computed(() => resolutions.filter(value => value !== 'UPDATE_GT_AFFILIATION' ||
    (selected.value.length > 0 && selected.value.every(id => {
        const row = detail.value?.record.id === id ? detail.value.record : rows.value.find(row => row.id === id)
        return row?.can_update_affiliation
    }))))
const decision = reactive({ review_status: 'REVIEWED', resolution: '', review_notes: '', resolved_gt_curation_id: '' })
const filterOptions = { source_type: ['HAS_PRECURATION_ID', 'NO_PRECURATION_ID'], match_status: ['CONFIRMED', 'NOT_FOUND_IN_GT', 'AMBIGUOUS_GDM_UUID', 'ALREADY_RECONCILED', 'CONFLICT'], match_method: ['PRECURATION_ID', 'GDM_UUID'], review_status: reviewStatuses, resolution: resolutions, execution_status: ['NOT_READY', 'PENDING', 'APPLIED', 'FAILED', 'NO_ACTION'] }
const flags = ['has_identity_warning', 'disease_match', 'moi_match', 'gdm_uuid_match', 'gene_match', 'affiliation_match', 'gt_is_retired']
const comparisons = [['Precuration ID', 'gci_precuration_id', 'gt_curation_id', 'precuration_id_match'], ['GDM UUID', 'gci_gdm_uuid', 'gt_gdm_uuid', 'gdm_uuid_match'], ['Gene', 'gci_gene_symbol', 'gt_gene_symbol', 'gene_match'], ['Disease', 'gci_mondo_id', 'gt_mondo_id', 'disease_match'], ['MoI', 'gci_moi_hp_id', 'gt_moi_hp_id', 'moi_match'], ['Affiliation', 'gci_affiliation_id', 'gt_affiliation_id', 'affiliation_match']]
const currentRun = computed(() => runs.value.find(run => run.uuid === filters.run_uuid))
const allSelected = computed(() => rows.value.length > 0 && rows.value.every(row => selected.value.includes(row.id)))
const readable = key => key.replaceAll('_', ' ')
watch(selected, () => { preview.value = null }, { deep: true })
watch(decision, () => { preview.value = null }, { deep: true })
async function perform(fn) {
    busy.value = true; error.value = ''; message.value = ''
    try { await fn() } catch (e) { error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || e.message }
    finally { busy.value = false }
}
async function loadRuns() { runs.value = (await window.axios.get(`${base}/runs`)).data }
async function fetchRows(n = 1) {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    const { data } = await window.axios.get(`${base}/records`, { params: { ...params, include_retired: includeRetired.value ? 1 : 0, page: n } })
    rows.value = data.data; page.value = data.current_page; lastPage.value = data.last_page; total.value = data.total
}
function refresh() { selected.value = []; preview.value = null; return perform(() => fetchRows()) }
function loadPage(n) { selected.value = []; return perform(() => fetchRows(n)) }
function selectVisible(checked) { selected.value = checked ? rows.value.map(row => row.id) : [] }
function createRun() { return perform(async () => { const { data } = await window.axios.post(`${base}/runs`, { label: label.value }); await loadRuns(); filters.run_uuid = data.uuid; selected.value = []; await fetchRows() }) }
function upload(data) { return perform(async () => { await window.axios.post(`${base}/runs/${filters.run_uuid}/uploads`, data); await loadRuns(); message.value = 'Upload validated and stored privately.' }) }
function reconcile() { return perform(async () => { await window.axios.post(`${base}/runs/${filters.run_uuid}/reconcile`); await loadRuns(); await fetchRows(); message.value = 'Reconciliation completed. GT curations were not modified.' }) }
function open(id) { return perform(async () => { detail.value = (await window.axios.get(`${base}/records/${id}`)).data; selected.value = [id]; const r = detail.value.record; Object.assign(decision, { review_status: r.review_status, resolution: r.resolution || '', review_notes: r.review_notes || '', resolved_gt_curation_id: r.resolved_gt_curation_id || '' }) }) }
function saveReview() { return perform(async () => { await window.axios.post(`${base}/review`, { ids: selected.value, ...decision, resolution: decision.resolution || null, resolved_gt_curation_id: decision.resolved_gt_curation_id || null }); preview.value = null; await fetchRows(page.value); if (detail.value) detail.value = (await window.axios.get(`${base}/records/${detail.value.record.id}`)).data; message.value = 'Review decisions saved. Preview before applying.' }) }
function previewSelected() { return perform(async () => { preview.value = (await window.axios.post(`${base}/preview`, { ids: selected.value })).data }) }
function execute() { return perform(async () => { const { data } = await window.axios.post(`${base}/execute`, { preview_uuid: preview.value.uuid }); preview.value = null; await fetchRows(page.value); if (detail.value) detail.value = (await window.axios.get(`${base}/records/${detail.value.record.id}`)).data; message.value = data.map(r => `Record ${r.id}: ${r.status}${r.error ? ' - ' + r.error : ''}`).join('; ') }) }
function exportSelected() { return perform(async () => { const { data } = await window.axios.post(`${base}/export`, { ids: selected.value }); const url = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })); const a = document.createElement('a'); a.href = url; a.download = 'gdmHasPreCurId.json'; a.click(); URL.revokeObjectURL(url); message.value = `${data.Count} records exported. GCI import remains a separate step.`; await fetchRows(page.value) }) }
onMounted(() => perform(async () => { await loadRuns(); await fetchRows() }))
</script>
<style scoped>
pre { white-space: pre-wrap; overflow-wrap: anywhere; }
.audit-history { max-height: 25rem; overflow: auto; }
</style>
