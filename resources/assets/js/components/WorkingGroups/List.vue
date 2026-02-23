<style></style>
<template>
    <div class="card">
        <div class="card-header">
            <h3>Working Groups</h3>
        </div>
        <div class="card-body">
            <div class="curations-table-container">
                <div class="row mb-2">
                    <div class="col-md-6 form-inline">
                        <label for="curations-filter-input">Search:</label>&nbsp;
                        <input v-model="filter" placeholder="search working groups" class="form-control" id="curations-filter-input" />
                    </div>
                </div>
                <DataTable
                    :value="filteredItems"
                    :paginator="true"
                    :rows="pageLength"
                    :small="true"
                    stripedRows
                    sortField="name"
                    :sortOrder="1"
                    @row-click="handleRowClick"
                    class="crsr-pointer"
                >
                    <Column field="id" header="Id" :sortable="true"></Column>
                    <Column field="name" header="Name" :sortable="true"></Column>
                </DataTable>
                <div class="mt-1">Total Records: {{filteredItems.length}}</div>
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
                pageLength: 25,
            }
        },
        computed: {
            ...mapGetters('workingGroups', {
                groups: 'Items'
            }),
            filteredItems() {
                const items = Object.values(this.groups);
                if (!this.filter) return items;
                const q = this.filter.toLowerCase();
                return items.filter(g =>
                    String(g.id).includes(q) || g.name.toLowerCase().includes(q)
                );
            },
        },
        methods: {
            ...mapActions('workingGroups', {
                getWorkingGroups: 'getAllItems'
            }),
            handleRowClick(event) {
                this.$router.push({path: '/working-groups/' + event.data.id})
            }
        },
        mounted() {
            this.getWorkingGroups();
        }
    }
</script>
