<template>
    <div class="curations-table">
        <div class="curations-toolbar mb-3" v-show="!loading">
            <div class="d-flex flex-wrap align-items-center justify-content-between toolbar-row">
                <div class="toolbar-search flex-grow-1 mr-3">
                    <div class="input-group input-group-sm">
                        <input
                            v-model="filter"
                            :id="searchFieldId"
                            class="form-control"
                            placeholder="Search curations by gene, disease, curator, status, or ID"
                        />
                        <div class="input-group-append">
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                @click="showAdvancedFilters = !showAdvancedFilters"
                            >
                                {{ showAdvancedFilters ? 'Hide filters' : 'More filters' }}
                                <span
                                    v-if="activeFilterCount"
                                    class="badge badge-light ml-1 toolbar-badge"
                                >
                                    {{ activeFilterCount }}
                                </span>
                            </button>

                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="btn btn-outline-secondary"
                                @click="clearFilters"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div class="toolbar-pagination">
                    <b-pagination
                        size="sm"
                        hide-goto-end-buttons
                        :total-rows="totalRows"
                        :per-page="pageLength"
                        v-model="currentPage"
                        class="curations-table-pagination my-0"
                    />
                </div>
            </div>

            <transition name="toolbar-slide">
                <div v-if="showAdvancedFilters" class="toolbar-filters mt-2">
                    <div class="row">
                        <div
                            v-for="field in filterableFields"
                            :key="field.key"
                            class="col-xl-3 col-lg-4 col-md-6 mb-2"
                        >
                            <label class="toolbar-label">{{ field.label }}</label>

                            <select
                                v-if="field.advancedFilter && field.advancedFilter.type === 'select'"
                                v-model="advancedFilters[field.key]"
                                class="form-control form-control-sm"
                            >
                                <option
                                    v-for="opt in field.advancedFilter.options"
                                    :key="String(opt.value)"
                                    :value="opt.value"
                                >
                                    {{ opt.text }}
                                </option>
                            </select>

                            <input
                                v-else
                                v-model="advancedFilters[field.key]"
                                class="form-control form-control-sm"
                                :placeholder="'Filter by ' + field.label"
                            />
                        </div>
                    </div>
                </div>
            </transition>
        </div>
        <div v-show="loading" class="text-center">
            <p class="lead">loading...</p>
        </div>
        <b-table striped hover 
            ref="table"
            :items="curationProvider" 
            :fields="fields" 
            :filter="filter"
            :per-page="pageLength"
            :current-page="currentPage"
            @sort-changed="handleSortChanged"
            :sort-by.sync="sortKey"
            :sort-desc.sync="sortDesc"
            :no-local-sorting="true"
            :show-empty="true"
        >     
            <template v-slot:table-busy>
                <center>Loading...</center>
            </template>
            <template v-slot:cell(gene_symbol)="{item}">
                <router-link
                    :id="'show-curation-'+item.id+'-link'" 
                    :to="'/curations/'+item.id"
                >
                    {{item.gene_symbol}}
                </router-link>
                <small v-if="item.hgnc_id">(hgnc:{{ item.hgnc_id }})</small>
            </template>
            <template v-slot:cell(mode_of_inheritance)="{item}">
                <div v-if="item.mode_of_inheritance !== null">
                    <div :title="item.mode_of_inheritance.name">
                        {{ item.mode_of_inheritance.abbreviation }}
                    </div>
                </div>
            </template>
            <template v-slot:cell(expert_panel)="{item}">
                <div>{{ item.expert_panel ? item.expert_panel.name : null }}</div>
            </template>
            <template v-slot:cell(curator)="{item}">
                <div>{{ item.curator ? item.curator.name : null }}</div>
            </template>
            <template v-slot:cell(current_status)="{item}">
                <div>{{ item.current_status ? item.current_status.name : null }}</div>
            </template>
            <template v-slot:cell(mondo_id)="{item}">
                <div>{{ getDiseaseEntityColumn(item) }}</div>
            </template>
            <template v-slot:cell(actions)="{item}" class="text-right">
                <div>
                    <router-link 
                        v-if="user.canEditCuration(item)"
                        :id="'edit-curation-'+item.id+'-btn'"
                        class="btn btn-secondary btn-sm"
                        :to="'/curations/'+item.id+'/edit'"
                    >
                        Edit
                    </router-link>
                    <delete-button :curation="item" class="btn-sm">
                        <span class="fa fa-trash">X</span>
                    </delete-button>
                </div>
            </template>
        </b-table>
        <div class="row border-top pt-4">
            <div class="col-md-6">Total Records: {{totalRows}}</div>
            <div class="col-md-6">
                <b-pagination 
                    size="sm"
                    hide-goto-end-buttons
                    :total-rows="totalRows"
                    :per-page="pageLength"
                    v-model="currentPage"
                    class="curations-table-pagination my-0 float-right" />
            </div>
        </div>
    </div>
</template>
<script>
import getPageOfCurations from '../../resources/curations/get_page_of_curations'
import uniqid from '../../helpers/uniqid'
import { mapGetters } from 'vuex'
import DeleteButton from './DeleteButton.vue'


export default {
    components: {
        DeleteButton
    },
    props: {
        sortBy: {
            type: String,
            default: 'gene_symbol'
        },
        sortDir: {
            type: Boolean,
            default: false
        },
        searchParams: {
            type: Object,
            default: function () {
                return {}
            }
        },
        pageLength: {
            type: Number,
            default: 10
        }
    },
    data() {
        return {

            filter: null,
            currentPage: 1,
            sortDesc: (this.sortDir == 'desc'),
            sortKey: JSON.parse(JSON.stringify(this.sortBy)),
            totalRows: 0,
            searchFieldId: 'search-filter-' + uniqid(),
            showAdvancedFilters: false,
            advancedFilters: {
                gene_symbol: '',
                mode_of_inheritance: '',
                mondo_id: '',
                expert_panel: '',
                curator: '',
                current_status: '',
                id: ''
            },
            fields: [
                {
                    key: 'gene_symbol',
                    label: 'Gene Symbol',
                    sortable: true,
                    filterable: true,
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'mode_of_inheritance',
                    label: 'MOI',
                    sortable: true,
                    filterable: true,
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'mondo_id',
                    label: 'Disease Entity',
                    sortable: true,
                    filterable: true,
                    thStyle: { width: '9rem' },
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'expert_panel',
                    label: 'Expert Panel',
                    sortable: true,
                    filterable: true,
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'curator',
                    label: 'Curator',
                    sortable: true,
                    filterable: true,
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'current_status',
                    label: 'Status',
                    sortable: false,
                    filterable: true,
                    thStyle: { width: '8rem' },
                    advancedFilter: {
                        type: 'select',
                        options: [
                            { value: '', text: 'Any' },
                            { value: 'Uploaded', text: 'Uploaded' },
                            { value: 'Precuration', text: 'Precuration' },
                            { value: 'Disease entity assigned', text: 'Disease entity assigned' },
                            { value: 'Precuration Complete', text: 'Precuration Complete' },
                            { value: 'Curation Provisional', text: 'Curation Provisional' },
                            { value: 'Curation Approved', text: 'Curation Approved' },
                            { value: 'Recuration assigned', text: 'Recuration assigned' },
                            { value: 'Retired Assignment', text: 'Retired Assignment' },
                            { value: 'Published', text: 'Published' }
                        ]
                    }
                },
                {
                    key: 'id',
                    label: 'Precuration ID',
                    sortable: true,
                    filterable: true,
                    advancedFilter: { type: 'text' }
                },
                {
                    key: 'actions',
                    label: '',
                    sortable: false,
                    thStyle: { width: '7rem' }
                }
            ],
            ctx: null
        }
    },
    computed: {
        ...mapGetters({ user: 'getUser' }),

        loading: function () {
            return false
        },

        filterableFields() {
            return this.fields.filter(f => f.filterable)
        },

        hasActiveFilters() {
            if (this.filter && this.filter.trim() !== '') {
                return true
            }

            return Object.values(this.advancedFilters).some(value => {
                return value !== null && value !== undefined && String(value).trim() !== ''
            })
        },

        activeFilterCount() {
            return Object.values(this.advancedFilters).filter(value => {
                return value !== null && value !== undefined && String(value).trim() !== ''
            }).length
        }
    },
    watch: {
        filter: function (to, from) {
            if (to !== from) {
                this.resetCurrentPage()
                this.$refs.table.refresh()
            }
        },
        advancedFilters: {
            deep: true,
            handler() {
                this.resetCurrentPage()
                this.$refs.table.refresh()
            }
        }
    },
    methods: {
        curationProvider(ctx, callback) {
            if (ctx == this.ctx) {
                return;
            }

            const context = {...ctx, ...this.searchParams, filters: JSON.stringify(this.advancedFilters)}
            getPageOfCurations(context)
                .then(response => {
                    this.totalRows = response.data.meta.total
                    callback(response.data.data)
                })
        },

        clearFilters() {
            this.filter = null
            this.advancedFilters = {
                gene_symbol: '',
                mode_of_inheritance: '',
                mondo_id: '',
                expert_panel: '',
                curator: '',
                current_status: '',
                id: ''
            }
            this.resetCurrentPage()
            this.$refs.table.refresh()
        },

        resetCurrentPage() {
            this.currentPage = 1
        },
        getDiseaseEntityColumn(item) {
            if (item.mondo_id && item.disease) {
                return item.mondo_id + ' (' + item.disease.name + ')'
            }

            if (item.disease_entity_notes) {
                let entity = item.disease_entity_notes
                if (entity.length > 32) {
                    entity = entity.substr(0, 32) + '…'
                }
                return entity
            }

            return null
        },
        handleFiltered() {
            this.resetCurrentPage()
        },
        handleSortChanged() {
            this.resetCurrentPage()
        }
    }
}
</script>
<style scoped>
.curations-toolbar {
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.75rem;
}

.toolbar-row {
    gap: 0.75rem;
}

.toolbar-search {
    min-width: 320px;
}

.toolbar-pagination {
    white-space: nowrap;
}

.toolbar-filters {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.4rem;
    padding: 0.75rem;
}

.toolbar-label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.toolbar-badge {
    font-size: 0.7rem;
    line-height: 1;
}

.toolbar-slide-enter-active,
.toolbar-slide-leave-active {
    transition: all 0.18s ease;
}

.toolbar-slide-enter,
.toolbar-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>