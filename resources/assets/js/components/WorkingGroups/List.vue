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
                        <label for="#curations-filter-input">Search:</label>&nbsp;
                        <input v-model="filter" placeholder="search working groups" class="form-control"
                            id="curations-filter-input" />
                    </div>
                </div>
                <br>

                <DataTable stripedRows :value="groups" :loading="loading"
                    paginator :rows="pageLength" :current-page="currentPage" :totalRecords="totalRecords"
                    selectionMode="single" @row-select="handleRowClick"
                >
                    <Column field="id" header="ID" sortable filterable></Column>
                    <Column field="name" header="Name" sortable filterable></Column>
                </DataTable>
                <div class="float-right">Total Records: {{ totalRecords }}</div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const store = useStore()
const router = useRouter()

const filter = ref(null)
const pageLength = ref(25)
const currentPage = ref(1)
const loading = ref(true)

const groups = computed(() => {
    return store.getters['workingGroups/Items']
})

const totalRecords = ref(0)

const getWorkingGroups = () => {
    store.dispatch('workingGroups/getAllItems')
        .then((response) => {
            console.log('Working Groups:', response)
            groups.value = response.data
            totalRecords.value = length(response.data)
        })
}

const handleRowClick = (event) => {
    router.push(`/working-groups/${event.data.id}`)
}

onMounted(async () => {
    await store.dispatch('workingGroups/getAllItems')
    console.log('Mounted Working Groups:', groups.value)
    totalRecords.value = groups.value.length
    console.log('Total Records:', totalRecords.value)
    loading.value = false
})
</script>