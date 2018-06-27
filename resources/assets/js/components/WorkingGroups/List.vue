<style></style>
<template>
    <div class="card">
        <div class="card-header">
            <h3>Working Groups</h3>
        </div>
        <div class="card-body">
            <div class="curations-table-container">
                <div class="row">
                    <div class="col-md-6 form-inline">
                        <label for="#curations-filter-input">Filter:</label>&nbsp;
                        <input v-model="filter" placeholder="filter results" class="form-control" id="curations-filter-input" />
                    </div>
                    <div class="col-md-6">
                        <b-pagination size="sm" hide-goto-end-buttons :total-rows="totalRows" :per-page="pageLength " v-model="currentPage" class="my-0 float-right" />    
                    </div>
                </div>
                <br>
                
                <b-table striped hover 
                    :items="tableItems" 
                    :fields="fields"
                    :filter="filter"
                    :per-page="pageLength"
                    :current-page="currentPage"
                    @filtered="onFiltered"
                    @row-clicked="handleRowClick"
                    tbody-tr-class="crsr-pointer"
                >            
                </b-table>
                <div class="float-right">Total Records: {{totalRows}}</div class="float-right">
            </div>        
        </div>
    </div>
</template>
<script>
    import {mapGetters, mapActions} from 'vuex'

    export default {
        data() {
            return {
                filter: null,
                pageLength: 8,
                currentPage: 1,
                fields: {
                    id: {
                        sortable: true
                    },
                    name: {
                        sortable: true
                    }
                },
                totalRows: 0
            }
        },
        computed: {
            ...mapGetters('workingGroups', {
                groups: 'Items'
            }),
            tableItems: function () {
                let items = Object.values(this.groups)
                this.totalRows = items.length;
                return items;
            },
        },
        methods: {
            ...mapActions('workingGroups', {
                getWorkingGroups: 'getAllItems'
            }),
            onFiltered (filteredItems) {
              // Trigger pagination to update the number of buttons/pages due to filtering
              this.currentPage = 1
              this.totalRows = filteredItems.length
            },
            handleRowClick($event) {
                this.$router.push({path: '/working-groups/'+$event.id})
            }
        },
        mounted() {
            this.getWorkingGroups();
        }
    }
</script>