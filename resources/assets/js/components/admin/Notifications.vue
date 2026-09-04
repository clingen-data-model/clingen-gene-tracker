<template>
    <div>
        <h2>Notifications</h2>
        <p class="text-muted">Application notification records. Viewing a record does not change its read state.</p>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>
        <b-card v-if="selected" class="mb-4" title="Notification Details">
            <dl class="row mb-0">
                <dt class="col-sm-2">Created</dt><dd class="col-sm-10">{{ dateTime(selected.created_at) }}</dd>
                <dt class="col-sm-2">Recipient</dt><dd class="col-sm-10">{{ recipient(selected) }}</dd>
                <dt class="col-sm-2">Type</dt><dd class="col-sm-10">{{ selected.readable_type || selected.type }}</dd>
                <dt class="col-sm-2">Read</dt><dd class="col-sm-10">{{ selected.read_at ? `Yes (${dateTime(selected.read_at)})` : 'No' }}</dd>
                <dt class="col-sm-2">Payload</dt><dd class="col-sm-10"><pre class="border rounded p-3 text-wrap">{{ payload(selected.data) }}</pre></dd>
            </dl>
            <b-button variant="secondary" @click="selected = null">Close</b-button>
        </b-card>
        <b-table :items="notifications" :fields="fields" :busy="loading" responsive striped>
            <template #table-busy><div class="text-center my-3">Loading notifications...</div></template>
            <template #cell(created_at)="{ item }">{{ dateTime(item.created_at) }}</template>
            <template #cell(recipient)="{ item }">{{ recipient(item) }}</template>
            <template #cell(read_at)="{ item }"><b-badge :variant="item.read_at ? 'secondary' : 'primary'">{{ item.read_at ? 'Read' : 'Unread' }}</b-badge></template>
            <template #cell(actions)="{ item }">
                <div class="d-flex gap-2">
                    <b-button size="sm" variant="outline-primary" @click="view(item)">View</b-button>
                    <b-button size="sm" variant="outline-danger" @click="remove(item)">Delete</b-button>
                </div>
            </template>
        </b-table>
        <p v-if="!loading && !notifications.length" class="text-muted">No notification records found.</p>
        <b-pagination v-if="totalRows > perPage" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'

const fields = [
    { key: 'created_at', label: 'Created' }, { key: 'recipient', label: 'Recipient' },
    { key: 'readable_type', label: 'Type' }, { key: 'read_at', label: 'Status' }, { key: 'actions', label: 'Actions' },
]
const notifications = ref([])
const loading = ref(true)
const selected = ref(null)
const currentPage = ref(1)
const perPage = 25
const totalRows = ref(0)
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })

function dateTime(value) { return value ? new Date(value).toLocaleString() : '—' }
function recipient(item) { return item.recipient?.name || item.recipient?.email || '—' }
function payload(value) {
    if (value === null || value === undefined || value === '') return '—'
    return typeof value === 'string' ? value : JSON.stringify(value, null, 2)
}
async function load() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/notifications', { params: { page: currentPage.value, per_page: perPage } })
        notifications.value = response.data.data
        totalRows.value = response.data.total
    } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load notifications.' }
    finally { loading.value = false }
}
async function view(item) {
    try { selected.value = (await window.axios.get(`/api/admin/notifications/${item.id}`)).data }
    catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load the notification.' }
}
async function remove(item) {
    if (!window.confirm(`Delete notification ${item.readable_type || item.id}?`)) return
    try {
        await window.axios.delete(`/api/admin/notifications/${item.id}`)
        if (selected.value?.id === item.id) selected.value = null
        successMessage.value = 'Notification deleted successfully.'
        await load()
    } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to delete the notification.' }
}
watch(currentPage, load)
onMounted(load)
</script>
