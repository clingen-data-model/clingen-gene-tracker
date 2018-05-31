<style></style>
<template>
    <div class="topics-table">
        <div class="row mb-2" v-show="totalRows > pageLength">
            <div class="col-md-6 form-inline">
                <label for="#topics-filter-input">Filter:</label>&nbsp;
                <input v-model="filter" placeholder="filter results" class="form-control" id="topics-filter-input" />
            </div>
            <div class="col-md-6">
                <b-pagination size="sm" hide-goto-end-buttons :total-rows="totalRows" :per-page="pageLength " v-model="currentPage" class="topics-table-pagination my-0 float-right" />    
            </div>
        </div>
        <b-table striped hover 
            :items="tableItems" 
            :fields="fields" 
            :filter="filter"
            :per-page="pageLength"
            :current-page="currentPage"
            @filtered="onFiltered"
        >            
            <template slot="gene_symbol" slot-scope="data">
                <router-link
                    :id="'show-topic-'+data.item.id+'-link'" 
                    :to="'/topics/'+data.item.id"
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
            <template slot="actions" slot-scope="data">
                <router-link
                    v-if="user.canEditTopic(data.item)"
                    :id="'edit-topic-'+data.item.id+'-btn'" 
                    class="btn btn-secondary float-right btn-sm" 
                    :to="'/topics/'+data.item.id+'/edit'"
                >
                    Edit
                </router-link>
            </template>
        </b-table>
        <div class="float-right">Total Records: {{totalRows}}</div class="float-right">
    </div>
</template>
<script>
    export default {
        props: {
            topics: {
                required: true,
                type: Array
            },
            pageLength: {
                type: Number,
                default: 10,
            }   
        },
        data() {
            return {
                user: user,
                filter: null,
                currentPage: 1,
                totalRows: null,
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
                    actions: {
                        label: '',
                        sortable: false,
                    }
                },
            }
        },
        computed: {
            tableItems: function () {
                let items = Object.values(this.topics);
                this.totalRows = items.length;
                return items;
            },            
        },
        methods: {
            onFiltered (filteredItems) {
              // Trigger pagination to update the number of buttons/pages due to filtering
              this.currentPage = 1
              this.totalRows = filteredItems.length
            }
        }
    }
</script>