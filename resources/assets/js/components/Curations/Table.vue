<style></style>
<template>
    <div class="curations-table">
        <div class="row mb-2">
            <div class="col-md-6 form-inline">
                <label :for="searchFieldId">Search:</label>&nbsp;
                <select v-model="filterField" class="form-control form-control-sm">
                    <option :value="null">Any Field</option>
                    <option :value="field.key" v-for="field in filterableFields" :key="field.key">{{field.label}}</option>
                </select>
                &nbsp;
                <input v-model="filter" placeholder="search curations" class="form-control form-control-sm" :id="searchFieldId" />
            </div>
        </div>
        <DataTable
            :value="items"
            :loading="loading"
            :lazy="true"
            :totalRecords="totalRows"
            :rows="pageLength"
            :paginator="true"
            :small="true"
            stripedRows
            :sortField="sortKey"
            :sortOrder="sortOrder"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            @page="onPage"
            @sort="onSort"
        >
            <Column field="gene_symbol" header="Gene Symbol" :sortable="true">
                <template #body="{data}">
                    <router-link
                        :id="'show-curation-'+data.id+'-link'"
                        :to="'/curations/'+data.id"
                    >
                        {{data.gene_symbol}}
                    </router-link>
                    <br>
                    <small v-if="data.hgnc_id">(hgnc:{{data.hgnc_id}})</small>
                </template>
            </Column>
            <Column field="mode_of_inheritance" header="MOI" :sortable="true">
                <template #body="{data}">
                    <div v-if="data.mode_of_inheritance !== null" :title="data.mode_of_inheritance.name">
                        {{data.mode_of_inheritance.abbreviation}}
                    </div>
                </template>
            </Column>
            <Column field="mondo_id" header="Disease Entity" :sortable="true" headerStyle="width: 9rem">
                <template #body="{data}">{{ getDiseaseEntityColumn(data) }}</template>
            </Column>
            <Column field="expert_panel" header="Expert Panel" :sortable="true">
                <template #body="{data}">{{ data.expert_panel ? data.expert_panel.name : null }}</template>
            </Column>
            <Column field="curator" header="Curator" :sortable="true">
                <template #body="{data}">{{ data.curator ? data.curator.name : null }}</template>
            </Column>
            <Column field="current_status" header="Status" :sortable="false" headerStyle="width: 8rem">
                <template #body="{data}">{{ data.current_status ? data.current_status.name : null }}</template>
            </Column>
            <Column field="id" header="Precuration ID" :sortable="true"></Column>
            <Column header="" headerStyle="width: 7rem">
                <template #body="{data}">
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
                </template>
            </Column>
        </DataTable>
        <div class="row border-top pt-2 mt-2">
            <div class="col-md-6">Total Records: {{totalRows}}</div>
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
                items: [],
                loading: false,
                filterField: null,
                filter: null,
                currentPage: 1,
                sortKey: JSON.parse(JSON.stringify(this.sortBy)),
                sortDesc: (this.sortDir == 'desc'),
                totalRows: 0,
                searchFieldId: `search-filter-${uniqid()}`,
                fields: [
                    { key: 'gene_symbol', label: 'Gene Symbol', sortable: true, filterable: true },
                    { key: 'mode_of_inheritance', label: 'MOI', sortable: true, filterable: true },
                    { key: 'mondo_id', label: 'Disease Entity', sortable: true, filterable: true },
                    { key: 'expert_panel', label: 'Expert Panel', sortable: true, filterable: true },
                    { key: 'curator', label: 'Curator', sortable: true },
                    { key: 'current_status', label: 'Status', sortable: false },
                    { key: 'id', label: 'Precuration ID', sortable: true },
                    { key: 'actions', label: '', sortable: false },
                ],
            }
        },
        computed: {
            ...mapGetters({user: 'getUser'}),
            sortOrder() {
                return this.sortDesc ? -1 : 1;
            },
            filterableFields() {
                return this.fields.filter(f => f.filterable)
            }
        },
        watch: {
            filter(to, from) {
                if (to !== from) {
                    this.currentPage = 1;
                    this.loadData();
                }
            },
            filterField() {
                this.currentPage = 1;
                this.loadData();
            }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                const params = {
                    currentPage: this.currentPage,
                    perPage: this.pageLength,
                    filter: this.filter,
                    sortBy: this.sortKey,
                    sortDesc: this.sortDesc,
                    filter_field: this.filterField,
                    ...this.searchParams,
                };
                getPageOfCurations(params)
                    .then(response => {
                        this.items = response.data.data;
                        this.totalRows = response.data.meta.total;
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            onPage(event) {
                // PrimeVue page event: page is 0-indexed
                this.currentPage = event.page + 1;
                this.loadData();
            },
            onSort(event) {
                // PrimeVue sort event: sortOrder is 1 (asc) or -1 (desc)
                this.sortKey = event.sortField;
                this.sortDesc = event.sortOrder === -1;
                this.currentPage = 1;
                this.loadData();
            },
            getDiseaseEntityColumn(item) {
                if (item.mondo_id && item.disease) {
                    return item.mondo_id + ' (' + item.disease.name + ')'
                }
                if (item.disease_entity_notes) {
                    let entity = item.disease_entity_notes;
                    if (entity.length > 32) {
                        entity = entity.substr(0, 32) + '…'
                    }
                    return entity
                }
                return null
            },
        }
    }
</script>
