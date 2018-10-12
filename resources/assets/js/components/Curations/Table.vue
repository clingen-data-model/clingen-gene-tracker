<style></style>
<template>
    <div class="curations-table">
        <div v-show="!loading && curations.length == 0"
            class="alert alert-secondary pl-2 pr-2 pt-2 pb-2"
        >
            <slot name="no-curations">No curations found.</slot>
        </div>
        <div class="row mb-2" v-show="!loading">
            <div class="col-md-6 form-inline">
                <label for="#curations-filter-input">Search:</label>&nbsp;
                <input v-model="filter" placeholder="search curations" class="form-control" id="curations-filter-input" />
            </div>
            <div class="col-md-6">
                <b-pagination size="sm" hide-goto-end-buttons :total-rows="totalRows" :per-page="pageLength " v-model="currentPage" class="curations-table-pagination my-0 float-right" />    
            </div>
        </div>
        <div v-show="loading" class="text-center">
            <p class="lead">loading...</p>
        </div>
        <b-table striped hover 
            :items="tableItems" 
            :fields="fields" 
            :filter="filter"
            :per-page="pageLength"
            :current-page="currentPage"
            @filtered="onFiltered"
            :sort-by.sync="sortKey"
            :sort-desc.sync="sortDesc"
            v-show="!$store.state.loading && curations.length >0"
        >     
            <template slot="gene_symbol" slot-scope="data">
                <router-link
                    :id="'show-curation-'+data.item.id+'-link'" 
                    :to="'/curations/'+data.item.id"
                >
                    {{data.item.gene_symbol}}
                </router-link>
            </template>
            <template slot="expert_panel" slot-scope="data">
                {{(data.item.expert_panel) ? data.item.expert_panel.name : null}}
            </template>
            <template slot="curator" slot-scope="data">
                {{(data.item.curator) ? data.item.curator.name : null}}
            </template>
            <template slot="current_status" slot-scope="data">
                {{(data.item.current_status) ? data.item.current_status.name : null}}
            </template>
            <template slot="mondo_id" slot-scope="data">
                {{ getDiseaseEntityColumn(data.item) }}
            </template>
            <div slot="actions" slot-scope="data" class="text-right">
                <router-link
                    v-if="user.canEditCuration(data.item)"
                    :id="'edit-curation-'+data.item.id+'-btn'" 
                    class="btn btn-secondary btn-sm" 
                    :to="'/curations/'+data.item.id+'/edit'"
                >
                    Edit
                </router-link>
                <delete-button :curation="data.item" class="btn-sm">
                    <span class="fa fa-trash">X</span>
                </delete-button>
            </div>
        </b-table>
        <div class="float-right mr-3 mb-3">Total Records: {{totalRows}}</div>
    </div>
</template>
<script>
    import DeleteButton from './DeleteButton'

    export default {
        components: {
            DeleteButton
        },
        props: {
            curations: {
                required: true,
                type: Array
            },
            pageLength: {
                type: Number,
                default: 100,
            },
            sortBy: {
                type: String,
                default: 'gene_symbol'
            },
            sortDir: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                user: user,
                filter: null,
                currentPage: 1,
                totalRows: null,
                sortDesc: false,
                sortKey: null,
                fields: {
                    gene_symbol: {
                        label: 'Gene Symbol',
                        sortable: true
                    },
                    expert_panel: {
                        label: 'Expert Panel',
                        sortable: true,
                    },
                    curator: {
                        label: 'Curator',
                        sortable: true,
                    },
                    current_status: {
                        label: 'Status',
                        sortable: true,
                    },
                    mondo_id: {
                        label: 'Disease Entity',
                        sortable: true,
                    },
                    actions: {
                        label: '',
                        sortable: false,
                        thStyle: {
                            width: "5rem"
                        }
                    }
                },
            }
        },
        computed: {
            tableItems: function () {
                let items = Object.values(this.curations);
                this.totalRows = items.length;
                return items;
            },
            loading: function () {
                return this.$store.getters.loading && this.curations.length == 0;
            },
        },
        methods: {
            getDiseaseEntityColumn (item) {
                if (item.mondo_id) {
                    return item.mondo_id
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
            onFiltered (filteredItems) {
              // Trigger pagination to update the number of buttons/pages due to filtering
              this.currentPage = 1
              this.totalRows = filteredItems.length
            }
        },
        mounted() {
            this.sortKey = JSON.parse(JSON.stringify(this.sortBy));
            this.sortDesc = (this.sortDir == 'desc');
        }
    }
</script>