<style></style>
<template>
    <div class="curations-table">
        <div class="row mb-2" v-show="!loading">
            <div class="col-md-6 form-inline">
                <label :for="searchFieldId">Search:</label>&nbsp;
                <select name="" id="" v-model="filterField" class="form-control form-control-sm">
                    <option :value="null">Any Field</option>
                    <option :value="field.key" v-for="field in filterableFields" :key="field.name">{{field.label}}</option>
                </select>
                &nbsp;
                <input v-model="filter" placeholder="search curations" class="form-control form-control-sm" :id="searchFieldId" />
            </div>
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
                <small v-if="item.hgnc_id">(hgnc:{{item.hgnc_id}})</small>
            </template>
            <template v-slot:cell(mode_of_inheritance)="{item}">
                <div v-if="item.mode_of_inheritance !== null">
                    <div :title="item.mode_of_inheritance.name">
                        {{item.mode_of_inheritance.abbreviation}}
                    </div>
                </div>
            </template>
            <template v-slot:cell(expert_panel)="{item}">
                <div>{{(item.expert_panel) ? item.expert_panel.name : null}}</div>
            </template>
            <template v-slot:cell(curator)="{item}">
                <div>{{(item.curator) ? item.curator.name : null}}</div>
            </template>
            <template v-slot:cell(current_status)="{item}">
                <div>{{(item.current_status) ? item.current_status.name : null}}</div>
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
                filterField: null,
                filter: null,
                currentPage: 1,
                sortDesc: (this.sortDir == 'desc'),
                sortKey: JSON.parse(JSON.stringify(this.sortBy)),
                totalRows: 0,
                searchFieldId: `search-filter-${uniqid()}`,
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
                        thStyle: {
                            width: "9rem"
                        }
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
                        // sortable: true,
                        sortable: false,
                        // filterable: true,
                        thStyle: {
                            width: "8rem"
                        }
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
                        thStyle: {
                            width: "7rem"
                        }
                    }
                ],
                ctx: null
            }
        },
        computed: {
            ...mapGetters({user: 'getUser'}),
            loading: function () {
                return false;
            },
            filterableFields() {
                return this.fields.filter(f => f.filterable)
            }
        },
        watch: {
            filter: function (to, from) {
                if (to != from) {
                    this.resetCurrentPage();
                }
            },
            filterField: function () {
                this.$refs.table.refresh()
            }
        },
        methods: {
            curationProvider(ctx, callback) {
                if (ctx == this.ctx) {
                    return;
                }

                const context = {...ctx, ...this.searchParams, ...{filter_field: this.filterField}};
                if (this.filterField) {
                    context.filter_field = this.filterField;
                }
                getPageOfCurations(context)
                    .then(response => {
                        this.totalRows = response.data.meta.total
                        callback(response.data.data)
                    })
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
            handleFiltered () {
              // Trigger pagination to update the number of buttons/pages due to filtering
              this.resetCurrentPage();
            },
            handleSortChanged() {
                this.resetCurrentPage();
            }
        }
    }
</script>