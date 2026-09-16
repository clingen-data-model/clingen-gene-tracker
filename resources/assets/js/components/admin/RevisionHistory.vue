<template>
    <section class="card mb-4" aria-labelledby="revision-history-title">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <h3 id="revision-history-title" class="h5 text-break">Revisions: {{ name }}</h3>
                <b-button variant="secondary" size="sm" @click="emit('close')">Close Revisions</b-button>
            </div>
            <p v-if="loading" role="status">Loading revision history...</p>
            <p v-else-if="errorMessage" role="alert" class="text-danger">{{ errorMessage }}</p>
            <p v-else-if="!revisions.length" class="text-muted">No revision history found.</p>
            <div v-else class="table-responsive">
                <table class="table table-striped revision-table">
                    <thead><tr><th scope="col">Changed field</th><th scope="col">Old value</th><th scope="col">New value</th><th scope="col">Changed by</th><th scope="col">Changed at</th></tr></thead>
                    <tbody>
                        <tr v-for="revision in revisions" :key="revision.id">
                            <td>{{ revision.field }}</td>
                            <td class="revision-value">{{ displayValue(revision.old_value) }}</td>
                            <td class="revision-value">{{ displayValue(revision.new_value) }}</td>
                            <td>{{ revision.changed_by }}</td>
                            <td>{{ dateTime(revision.changed_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    endpoint: { type: String, required: true },
    name: { type: String, required: true },
})
const emit = defineEmits(['close'])
const revisions = ref([])
const loading = ref(true)
const errorMessage = ref('')

function displayValue(value) { return value === null ? '(null)' : value === '' ? '(empty)' : value }
function dateTime(value) { return value ? new Date(value).toLocaleString() : '—' }

watch(() => props.endpoint, async (endpoint, previous, onCleanup) => {
    let active = true
    onCleanup(() => { active = false })
    loading.value = true
    revisions.value = []
    errorMessage.value = ''
    try {
        const response = await window.axios.get(endpoint)
        if (active) revisions.value = response.data.data
    } catch (error) {
        if (active) errorMessage.value = error.response?.data?.message || 'Unable to load revision history.'
    } finally {
        if (active) loading.value = false
    }
}, { immediate: true })
</script>

<style scoped>
.revision-table { table-layout: fixed; min-width: 640px; width: 100%; }
.revision-table td, .revision-table th { overflow-wrap: anywhere; }
.revision-value { white-space: pre-wrap; }
</style>
