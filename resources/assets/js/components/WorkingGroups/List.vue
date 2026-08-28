<style></style>
<template>
    <div class="card">
        <div class="card-header">
            <h3>Working Groups</h3>
        </div>
        <div class="card-body">
            <div class="curations-table-container">
                <div class="row">
                    <div class="col-md-6 d-flex align-items-center">
                        <label for="#curations-filter-input">Search:</label>&nbsp;
                        <input v-model="filter" placeholder="search working groups" class="form-control" id="curations-filter-input" />
                    </div>
                    <div class="col-md-6">
                        <b-pagination size="sm" hide-goto-end-buttons :total-rows="totalRows" :per-page="pageLength " v-model="currentPage" class="my-0 float-end" />
                    </div>
                </div>
                <br>
                
                <b-table striped hover 
                    :items="tableItems" 
                    :fields="fields"
                    :filter="filter"
                    :per-page="pageLength"
                    :current-page="currentPage"
                    v-model:sort-by="sortBy"
                    @filtered="onFiltered"
                >         
                    <template #cell(name)="{ item }">
                        <router-link :to="`/working-groups/${item.id}`">
                            {{ item.name }}
                        </router-link>
                    </template>   
                </b-table>
                <div class="float-end">Total Records: {{totalRows}}</div>
            </div>        
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const filter = ref(null)
const pageLength = ref(25)
const currentPage = ref(1)
const totalRows = ref(0)
const sortBy = ref([
    {
        key: 'name',
        order: 'asc'
    }
])
const fields = [
    {
        key: 'id',
        sortable: true
    },
    {
        key: 'name',
        sortable: true
    }
]

const groups = computed(() => store.getters['workingGroups/Items'])
const tableItems = computed(() => {
    let items = Object.values(groups.value)
    totalRows.value = items.length
    return items
})

function onFiltered(filteredItems) {
    // Trigger pagination to update the number of buttons/pages due to filtering
    currentPage.value = 1
    totalRows.value = filteredItems.length
}

onMounted(() => {
    store.dispatch('workingGroups/getAllItems')
})
</script>
