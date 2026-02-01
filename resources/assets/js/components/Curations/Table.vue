<style></style>
<template>
    <div class="curations-table">
        <div class="row mb-2" v-show="!loading">
            <div class="col-md-6 form-inline">
                <label :for="searchFieldId">Search:</label>&nbsp;
                <select name="" id="" v-model="filterField" class="form-control form-control-sm">
                    <option :value="null">Any Field</option>
                    <option :value="field.key" v-for="field in filterableFields" :key="field.key">{{field.label}}</option>
                </select>
                &nbsp;
                <input v-model="filter" placeholder="search curations" class="form-control form-control-sm" :id="searchFieldId" />
            </div>
            <div class="col-md-6">
                <Paginator
                    :rows="pageLength"
                    :totalRecords="totalRows"
                    :first="(currentPage - 1) * pageLength"
                    @page="onPage"
                    class="float-end my-0"
                    template="PrevPageLink CurrentPageReport NextPageLink"
                />
            </div>
        </div>
        <div v-show="loading" class="text-center">
            <p class="lead">loading...</p>
        </div>
        <DataTable
            :value="items"
            :loading="tableLoading"
            lazy
            :totalRecords="totalRows"
            :rows="pageLength"
            :sortField="sortKey"
            :sortOrder="sortOrder"
            @sort="onSort"
            stripedRows
            showGridlines
            ref="table"
        >
            <template #empty>No curations found.</template>
            <template #loading>Loading...</template>

            <Column field="gene_symbol" header="Gene Symbol" sortable>
                <template #body="{data}">
                    <router-link
                        :id="'show-curation-'+data.id+'-link'"
                        :to="'/curations/'+data.id"
                    >
                        {{data.gene_symbol}}
                    </router-link>
                    <small v-if="data.hgnc_id">(hgnc:{{data.hgnc_id}})</small>
                </template>
            </Column>
            <Column field="mode_of_inheritance" header="MOI" sortable>
                <template #body="{data}">
                    <div v-if="data.mode_of_inheritance !== null">
                        <div :title="data.mode_of_inheritance.name">
                            {{data.mode_of_inheritance.abbreviation}}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="mondo_id" header="Disease Entity" sortable style="width: 9rem">
                <template #body="{data}">
                    <div>{{ getDiseaseEntityColumn(data) }}</div>
                </template>
            </Column>
            <Column field="expert_panel" header="Expert Panel" sortable>
                <template #body="{data}">
                    <div>{{(data.expert_panel) ? data.expert_panel.name : null}}</div>
                </template>
            </Column>
            <Column field="curator" header="Curator" sortable>
                <template #body="{data}">
                    <div>{{(data.curator) ? data.curator.name : null}}</div>
                </template>
            </Column>
            <Column field="current_status" header="Status" style="width: 8rem">
                <template #body="{data}">
                    <div>{{(data.current_status) ? data.current_status.name : null}}</div>
                </template>
            </Column>
            <Column field="id" header="Precuration ID" sortable></Column>
            <Column header="" style="width: 7rem">
                <template #body="{data}">
                    <div>
                        <router-link
                            v-if="user.canEditCuration(data)"
                            :id="'edit-curation-'+data.id+'-btn'"
                            class="btn btn-secondary btn-sm"
                            :to="'/curations/'+data.id+'/edit'"
                        >
                            Edit
                        </router-link>
                        <delete-button :curation="data" class="btn-sm">
                            <span class="fa fa-trash">X</span>
                        </delete-button>
                    </div>
                </template>
            </Column>
        </DataTable>
        <div class="row border-top pt-4">
            <div class="col-md-6">Total Records: {{totalRows}}</div>
            <div class="col-md-6">
                <Paginator
                    :rows="pageLength"
                    :totalRecords="totalRows"
                    :first="(currentPage - 1) * pageLength"
                    @page="onPage"
                    class="float-end my-0"
                    template="PrevPageLink CurrentPageReport NextPageLink"
                />
            </div>
        </div>
    </div>
</template>
<script>
    import DataTable from 'primevue/datatable'
    import Column from 'primevue/column'
    import Paginator from 'primevue/paginator'
    import getPageOfCurations from '../../resources/curations/get_page_of_curations'
    import uniqid from '../../helpers/uniqid'
    import { mapState } from 'pinia'
    import { useAppStore } from '../../stores/app'
    import DeleteButton from './DeleteButton.vue'

    export default {
        components: {
            DataTable,
            Column,
            Paginator,
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
                filterField: null,
                filter: null,
                currentPage: 1,
                sortDesc: (this.sortDir == 'desc'),
                sortKey: JSON.parse(JSON.stringify(this.sortBy)),
                totalRows: 0,
                searchFieldId: `search-filter-${uniqid()}`,
                items: [],
                tableLoading: false,
                fields: [
                    {
                        key: 'gene_symbol',
                        label: 'Gene Symbol',
                        sortable: true,
                        filterable: true,
                    },
                    {
                        key: 'mode_of_inheritance',
                        label: 'MOI',
                        sortable: true,
                        filterable: true,
                    },
                    {
                        key: 'mondo_id',
                        label: 'Disease Entity',
                        sortable: true,
                        filterable: true,
                    },
                    {
                        key: 'expert_panel',
                        label: 'Expert Panel',
                        filterable: true,
                        sortable: true,
                    },
                    {
                        key: 'curator',
                        label: 'Curator',
                        sortable: true,
                    },
                    {
                        key: 'current_status',
                        label: 'Status',
                        sortable: false,
                    },
                    {
                        key: 'id',
                        label: 'Precuration ID',
                        sortable: true,
                    },
                    {
                        key: 'actions',
                        label: '',
                        sortable: false,
                    }
                ],
            }
        },
        computed: {
            ...mapState(useAppStore, {user: 'getUser'}),
            loading: function () {
                return false;
            },
            filterableFields() {
                return this.fields.filter(f => f.filterable)
            },
            sortOrder() {
                return this.sortDesc ? -1 : 1;
            }
        },
        watch: {
            filter: function (to, from) {
                if (to != from) {
                    this.resetCurrentPage();
                    this.loadData();
                }
            },
            filterField: function () {
                this.loadData();
            }
        },
        methods: {
            loadData() {
                this.tableLoading = true;
                const context = {
                    currentPage: this.currentPage,
                    perPage: this.pageLength,
                    sortBy: this.sortKey,
                    sortDesc: this.sortDesc,
                    filter: this.filter,
                    filter_field: this.filterField,
                    ...this.searchParams,
                };
                getPageOfCurations(context)
                    .then(response => {
                        this.totalRows = response.data.meta.total;
                        this.items = response.data.data;
                        this.tableLoading = false;
                    })
                    .catch(() => {
                        this.tableLoading = false;
                    });
            },
            onPage(event) {
                this.currentPage = event.page + 1;
                this.loadData();
            },
            onSort(event) {
                this.sortKey = event.sortField;
                this.sortDesc = event.sortOrder === -1;
                this.resetCurrentPage();
                this.loadData();
            },
            resetCurrentPage () {
                this.currentPage = 1;
            },
            getDiseaseEntityColumn (item) {
                if (item.mondo_id && item.disease) {
                    return item.mondo_id + ' ('+item.disease.name+')'
                }

                if (item.disease_entity_notes) {
                    let entity = item.disease_entity_notes;
                    if (entity.length > 32) {
                        entity = entity.substr(0, 32)+'…'
                    }
                    return entity
                }

                return null
            },
            handleSortChanged() {
                this.resetCurrentPage();
            }
        },
        mounted() {
            this.loadData();
        }
    }
</script>
