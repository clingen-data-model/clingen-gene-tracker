<template>
    <div>
        <h2>Email Log</h2>
        <p class="text-muted">Read-only records of mail sent by the application.</p>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>
        <b-card v-if="selected" class="mb-4" title="Email Details">
            <dl class="row mb-0">
                <dt class="col-sm-2">Sent</dt><dd class="col-sm-10">{{ dateTime(selected.created_at) }}</dd>
                <dt class="col-sm-2">From</dt><dd class="col-sm-10">{{ addresses(selected.from) }}</dd>
                <dt class="col-sm-2">Sender</dt><dd class="col-sm-10">{{ addresses(selected.sender) }}</dd>
                <dt class="col-sm-2">To</dt><dd class="col-sm-10">{{ addresses(selected.to) }}</dd>
                <dt class="col-sm-2">CC</dt><dd class="col-sm-10">{{ addresses(selected.cc) }}</dd>
                <dt class="col-sm-2">BCC</dt><dd class="col-sm-10">{{ addresses(selected.bcc) }}</dd>
                <dt class="col-sm-2">Reply To</dt><dd class="col-sm-10">{{ addresses(selected.reply_to) }}</dd>
                <dt class="col-sm-2">Subject</dt><dd class="col-sm-10">{{ selected.subject || '—' }}</dd>
                <dt class="col-sm-2">Body</dt><dd class="col-sm-10"><pre class="border rounded p-3 text-wrap">{{ selected.body || '—' }}</pre></dd>
            </dl>
            <b-button variant="secondary" @click="selected = null">Close</b-button>
        </b-card>
        <b-table :items="emails" :fields="fields" :busy="loading" responsive striped>
            <template #table-busy><div class="text-center my-3">Loading emails...</div></template>
            <template #cell(created_at)="{ item }">{{ dateTime(item.created_at) }}</template>
            <template #cell(from)="{ item }">{{ addresses(item.from) }}</template>
            <template #cell(to)="{ item }">{{ addresses(item.to) }}</template>
            <template #cell(actions)="{ item }"><b-button size="sm" variant="outline-primary" @click="view(item)">View</b-button></template>
        </b-table>
        <p v-if="!loading && !emails.length" class="text-muted">No email records found.</p>
        <b-pagination v-if="totalRows > perPage" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'

const fields = [
    { key: 'created_at', label: 'Sent' }, { key: 'from', label: 'From' },
    { key: 'to', label: 'To' }, { key: 'subject', label: 'Subject' }, { key: 'actions', label: 'Actions' },
]
const emails = ref([])
const loading = ref(true)
const selected = ref(null)
const currentPage = ref(1)
const perPage = 25
const totalRows = ref(0)
const errorMessage = ref('')
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })

function addresses(value) {
    if (!value || typeof value !== 'object') return value || '—'
    const entries = Object.entries(value)
    return entries.length ? entries.map(([email, name]) => name ? `${name} <${email}>` : email).join(', ') : '—'
}
function dateTime(value) { return value ? new Date(value).toLocaleString() : '—' }
async function load() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/emails', { params: { page: currentPage.value, per_page: perPage } })
        emails.value = response.data.data
        totalRows.value = response.data.total
    } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load emails.' }
    finally { loading.value = false }
}
async function view(item) {
    try { selected.value = (await window.axios.get(`/api/admin/emails/${item.id}`)).data }
    catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load the email.' }
}
watch(currentPage, load)
onMounted(load)
</script>
