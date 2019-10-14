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
            <template v-slot:cell(gene_symbol)="{item}">
                <router-link
                    :id="'show-curation-'+item.id+'-link'" 
                    :to="'/curations/'+item.id"
                >
                    {{item.gene_symbol}}
                </router-link>
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
                fields: [
                    {
                        key: 'gene_symbol',
                        label: 'Gene Symbol',
                        sortable: true
                    },
                    {
                        key: 'expert_panel',
                        label: 'Expert Panel',
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
                        sortable: true,
                        thStyle: {
                            width: "8rem"
                        }
                    },
                    {
                        key: 'mondo_id',
                        label: 'Disease Entity',
                        sortable: true,
                        thStyle: {
                            width: "9rem"
                        }
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