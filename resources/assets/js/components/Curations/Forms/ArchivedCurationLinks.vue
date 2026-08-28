<template>
    <div class="mt-4">
        <hr>

        <div v-if="isArchived">
            <h5>Linked Current Curations</h5>

            <ul v-if="linkedCurrentCurations.length" class="list-group">
                <li
                    v-for="curation in linkedCurrentCurations"
                    :key="curation.id"
                    class="list-group-item"
                >
                    <strong>{{ curation.gene_symbol || 'Untitled curation' }}</strong>
                    <span v-if="curation.expert_panel"> - {{ curation.expert_panel.name }}</span>
                    <span v-if="curation.uuid" class="text-muted small d-block">
                        UUID: {{ curation.uuid }}
                    </span>
                </li>
            </ul>

            <div v-else class="text-muted">
                No linked current curations.
            </div>
        </div>

        <div v-else>
            <h5>Linked Archived Curations</h5>

            <div v-if="editable">
                <b-form-group label="Search archived curations">
                    <b-form-input
                        v-model="search"
                        type="text"
                        placeholder="Search by gene symbol, Precuration ID, HGNC ID, or GCI UUID"
                    ></b-form-input>
                </b-form-group>

                <div v-if="searchLoading" class="text-muted mb-2">
                    Searching...
                </div>

                <div v-if="searchError" class="alert alert-danger py-2">
                    {{ searchError }}
                </div>

                <ul v-if="filteredSearchResults.length" class="list-group mb-3">
                    <li
                        v-for="result in filteredSearchResults"
                        :key="result.id"
                        class="list-group-item d-flex justify-content-between align-items-center"
                    >
                        <div>
                            <router-link
                                :id="'show-curation-'+result.id+'-link'" 
                                :to="'/curations/'+result.id"
                            >
                                {{result.gene_symbol}}
                            </router-link>
                            <small v-if="result.hgnc_id">(hgnc:{{result.hgnc_id}})</small>  
                            <span v-if="result.expert_panel"> - {{ result.expert_panel.name }}</span>
                            <span class="badge bg-warning text-dark ms-2">Archived</span>
                            <span v-if="result.gdm_uuid" class="text-muted small d-block">
                                GCI Record: {{ result.gdm_uuid }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            @click="addArchivedCuration(result)"
                        >
                            Add
                        </button>
                    </li>
                </ul>
            </div>

            <ul v-if="linkedArchivedCurations.length" class="list-group">
                <li
                    v-for="curation in linkedArchivedCurations"
                    :key="curation.id"
                    class="list-group-item d-flex justify-content-between align-items-center"
                >
                    <div>
                        <strong>{{ curation.gene_symbol || 'Untitled curation' }}</strong>
                        <span v-if="curation.expert_panel"> - {{ curation.expert_panel.name }}</span>
                        <span class="badge bg-warning text-dark ms-2">Archived</span>
                        <span v-if="curation.uuid" class="text-muted small d-block">
                            UUID: {{ curation.uuid }}
                        </span>
                    </div>

                    <button
                        v-if="editable"
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        @click="removeArchivedCuration(curation.id)"
                    >
                        Remove
                    </button>
                </li>
            </ul>

            <div v-else class="text-muted">
                No linked archived curations.
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { debounce } from 'lodash'

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    editable: {
        type: Boolean,
        default: false,
    },
})
const emit = defineEmits(['update:modelValue'])

const search = ref('')
const searchLoading = ref(false)
const searchError = ref(null)
const searchResults = ref([])

const currentValue = computed(() => props.modelValue || {})
const isArchived = computed(() => Boolean(currentValue.value.is_archived))
const linkedArchivedCurations = computed(() => {
    return currentValue.value.linkedArchivedCurations
        || currentValue.value.linked_archived_curations
        || []
})
const linkedCurrentCurations = computed(() => {
    return currentValue.value.linkedCurrentCurations
        || currentValue.value.linked_current_curations
        || []
})
const selectedArchivedIds = computed(() => linkedArchivedCurations.value.map(item => item.id))
const filteredSearchResults = computed(() => {
    return searchResults.value.filter(result => !selectedArchivedIds.value.includes(result.id))
})

function emitUpdatedValue(patch = {}) {
    const next = {
        ...currentValue.value,
        ...patch,
    }

    emit('update:modelValue', next)
}

function syncArchivedIds(nextLinkedArchivedCurations) {
    emitUpdatedValue({
        linkedArchivedCurations: nextLinkedArchivedCurations,
        archived_curation_ids: nextLinkedArchivedCurations.map(item => item.id),
    })
}

async function fetchArchivedCurations() {
    if (!props.editable) {
        searchResults.value = []
        return
    }

    if (!search.value || search.value.trim().length < 2) {
        searchResults.value = []
        return
    }

    searchLoading.value = true
    searchError.value = null

    try {
        const response = await axios.get('/api/curations/archived-curation-options', {
            params: { q: search.value.trim() }
        })
        const payload = response.data
        searchResults.value = Array.isArray(payload) ? payload : (payload.data || [])
    } catch (error) {
        searchError.value = 'Unable to search archived curations.'
        searchResults.value = []
    } finally {
        searchLoading.value = false
    }
}

const debouncedFetchArchivedCurations = debounce(fetchArchivedCurations, 300)
watch(search, debouncedFetchArchivedCurations)

function addArchivedCuration(curation) {
    if (selectedArchivedIds.value.includes(curation.id)) {
        return
    }

    const next = [...linkedArchivedCurations.value, curation]
    syncArchivedIds(next)
    search.value = ''
    searchResults.value = []
}

function removeArchivedCuration(curationId) {
    const next = linkedArchivedCurations.value.filter(item => item.id !== curationId)
    syncArchivedIds(next)
}

onUnmounted(() => {
    debouncedFetchArchivedCurations.cancel()
})
</script>
