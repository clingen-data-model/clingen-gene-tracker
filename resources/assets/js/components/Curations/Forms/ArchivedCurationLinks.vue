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

                <ul v-if="searchResults.length" class="list-group mb-3">
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
                            <span class="badge badge-warning ml-2">Archived</span>
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
                        <span class="badge badge-warning ml-2">Archived</span>
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

<script>
import _ from 'lodash'

export default {
    name: 'ArchivedCurationLinks',

    props: {
        value: {
            type: Object,
            required: true,
        },
        editable: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            search: '',
            searchLoading: false,
            searchError: null,
            searchResults: [],
        }
    },

    computed: {
        currentValue() {
            return this.value || {}
        },

        isArchived() {
            return Boolean(this.currentValue.is_archived)
        },

        linkedArchivedCurations() {
            return this.currentValue.linkedArchivedCurations
                || this.currentValue.linked_archived_curations
                || []
        },

        linkedCurrentCurations() {
            return this.currentValue.linkedCurrentCurations
                || this.currentValue.linked_current_curations
                || []
        },

        selectedArchivedIds() {
            return this.linkedArchivedCurations.map(item => item.id)
        },

        filteredSearchResults() {
            return this.searchResults.filter(result => !this.selectedArchivedIds.includes(result.id))
        },
    },

    watch: {
        search: _.debounce(function () {
            this.fetchArchivedCurations()
        }, 300),
    },

    methods: {
        emitUpdatedValue(patch = {}) {
            const next = {
                ...this.currentValue,
                ...patch,
            }

            this.$emit('input', next)
        },

        syncArchivedIds(linkedArchivedCurations) {
            this.emitUpdatedValue({
                linkedArchivedCurations,
                archived_curation_ids: linkedArchivedCurations.map(item => item.id),
            })
        },

        async fetchArchivedCurations() {
            if (!this.editable) {
                this.searchResults = []
                return
            }

            if (!this.search || this.search.trim().length < 2) {
                this.searchResults = []
                return
            }

            this.searchLoading = true
            this.searchError = null

            try {
                const response = await axios.get('/api/curations/archived-curation-options', {
                    params: { q: this.search.trim() }
                })
                const payload = response.data
                this.searchResults = Array.isArray(payload) ? payload : (payload.data || [])
            } catch (error) {
                this.searchError = 'Unable to search archived curations.'
                this.searchResults = []
            } finally {
                this.searchLoading = false
            }
        },

        addArchivedCuration(curation) {
            if (this.selectedArchivedIds.includes(curation.id)) {
                return
            }

            const next = [...this.linkedArchivedCurations, curation]
            this.syncArchivedIds(next)
            this.search = ''
            this.searchResults = []
        },

        removeArchivedCuration(curationId) {
            const next = this.linkedArchivedCurations.filter(item => item.id !== curationId)
            this.syncArchivedIds(next)
        },
    },
}
</script>