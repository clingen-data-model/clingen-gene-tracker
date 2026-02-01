<style></style>
<template>
    <div class="card">
        <div class="card-header">
            <h3>Working Groups</h3>
        </div>
        <div class="card-body">
            <div class="curations-table-container">
                <DataTable
                    :value="tableItems"
                    stripedRows
                    :paginator="true"
                    :rows="pageLength"
                    :globalFilterFields="['id', 'name']"
                    v-model:filters="dtFilters"
                    @row-click="handleRowClick"
                    :rowClass="() => 'crsr-pointer'"
                    size="small"
                >
                    <template #header>
                        <div class="d-flex justify-content-between">
                            <div>
                                <label for="curations-filter-input">Search:</label>&nbsp;
                                <input v-model="dtFilters['global'].value" placeholder="search working groups" class="form-control d-inline-block w-auto" id="curations-filter-input" />
                            </div>
                            <div class="text-muted align-self-center">Total Records: {{ tableItems.length }}</div>
                        </div>
                    </template>
                    <Column field="id" header="Id" sortable></Column>
                    <Column field="name" header="Name" sortable></Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useWorkingGroupsStore } from '../../stores/workingGroups'
    import DataTable from 'primevue/datatable'
    import Column from 'primevue/column'

    export default {
        components: {
            DataTable,
            Column,
        },
        data() {
            return {
                pageLength: 25,
                dtFilters: {
                    global: { value: null, matchMode: 'contains' },
                },
            }
        },
        computed: {
            ...mapState(useWorkingGroupsStore, {
                groups: 'Items'
            }),
            tableItems: function () {
                return Object.values(this.groups)
            },
        },
        methods: {
            ...mapActions(useWorkingGroupsStore, {
                getWorkingGroups: 'getAllItems'
            }),
            handleRowClick(event) {
                this.$router.push({path: '/working-groups/'+event.data.id})
            }
        },
        mounted() {
            this.getWorkingGroups();
        }
    }
</script>
