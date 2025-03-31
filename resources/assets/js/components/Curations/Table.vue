<template>
    <div class="curations-table">
        <div class="row mb-2" v-show="!loading">
<!--             <div class="col-md-6 form-inline">
                <label :for="searchFieldId">Search:</label>&nbsp;
                <select name="" id="" v-model="filterField" class="form-control form-control-sm">
                    <option :value="null">Any Field</option>
                    <option :value="field.key" v-for="field in filterableFields" :key="field.name">{{ field.label }}
                    </option>
                </select>
                &nbsp;
                <input v-model="filter" placeholder="search curations" class="form-control form-control-sm"
                    :id="searchFieldId" />
            </div> -->
        </div>
        <div>
            Search:
            <input v-model="filter" placeholder="search curations" class="form-control form-control-sm" />
        </div>
        <DataTable stripedRows :value="curations" :loading="loading" lazy
            paginator :rows="pageLength" :current-page="currentPage" :totalRecords="totalRows"
            @page="handlePage" @sort="handleSort"
            class="curations-data-table">
            <Column field="gene_symbol" header="Gene Symbol" sortable filterable>
                <template #body="{ data: item }">
                    <router-link :id="`show-curation-${item.id}-link`" :to="`/curations/${item.id}`">
                        {{ item.gene_symbol }}
                    </router-link>
                    <div v-if="item.hgnc_id">
                        (hgnc: <a :href="`https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:${item.hgnc_id}`" target="_blank">
                            {{ item.gene_symbol }}
                        </a>)
                    </div>
                </template>
            </Column>
            <Column field="mode_of_inheritance" header="MOI" sortable filterable>
                <template #body="{ data: item }">
                    <div v-if="item.mode_of_inheritance !== null">
                        <div :title="item.mode_of_inheritance.name">
                            {{ item.mode_of_inheritance.abbreviation }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="mondo_id" header="Disease Entity" sortable filterable
                style="width: '9rem'">
                <template #body="{ data: item }">
                    <div>
                        <div v-if="item.mondo_id" :title="item.mondo_id">
                            <a :href="mondo_iri(item)" target="_blank">
                                {{ item.mondo_id }} 
                            </a>
                        </div>
                        <div v-if="item.disease" :title="item.disease.name">
                            ({{ item.disease.name }})
                        </div>
                        <div v-if="item.disease_entity_notes && !(item.mondo_id || item.disease)" :title="item.disease_entity_notes">
                            {{ truncateDiseaseEntityNotes(item) }}
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="expert_panel.name" header="Expert Panel"
                sortField="export_panel" sortable filterable
                style="width: '9rem'" />
            <Column field="curator.name" header="Curator" sortable filterable
                style="width: '9rem'" />
            <Column field="current_status.name" header="Status" style="width: '8rem'" />
            <Column field="id" header="Precuration ID" sortable />
            <Column field="id" header="Actions" style="width: '7rem'">
                <template #body="{ data: item }">
                    <div>
                        <router-link v-if="user.canEditCuration(item)" :id="'edit-curation-' + item.id + '-btn'"
                            class="btn btn-secondary btn-sm" :to="`/curations/${item.id}/edit`">
                            Edit
                        </router-link>
                        <delete-button :curation="item" class="btn-sm">
                            <span class="fa fa-trash">X</span>
                        </delete-button>
                    </div>
                </template>
            </Column>
        </DataTable>
        <div class="row border-top pt-4">
            <div class="col-md-6">Total Records: {{ totalRows }}</div>
        </div>
    </div>
</template>

<script setup>
import getPageOfCurations from '../../resources/curations/get_page_of_curations'
import DeleteButton from './DeleteButton.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps({
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
        default: () => ({})
    },
    pageLength: {
        type: Number,
        default: 10
    }
})

const filterField = ref(null)
const filter = ref(null)
const currentPage = ref(1)
const pageLength = ref(10)
const sortBy = ref('gene_symbol')
const sortDesc = ref(false)
const totalRows = ref(0)

const store = useStore()
const user = computed(() => {
    // Access the user from the store
    return store.getters.getUser || {}
})

const curations = ref([])

const loading = ref(false)

const filterableFields = computed(() => {
    return this.fields.value.filter(f => f.filterable)
})

const curationProvider = (ctx, callback) => {
    if (ctx == this.ctx) {
        return;
    }

    const context = { ...ctx, ...this.searchParams, ...{ filter_field: this.filterField } };
    if (this.filterField) {
        context.filter_field = this.filterField;
    }
    getPageOfCurations(context)
        .then(response => {
            this.totalRows.value = response.data.meta.total
            callback(response.data.data)
            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
}

const updateCurrentPage = () => {
    loading.value = true
    const context = {
        currentPage: currentPage.value,
        perPage: pageLength.value,
        sortBy: sortBy.value,
        sortDesc: sortDesc.value,
        filter_field: filterField.value,
        filter: filter.value
    }
    getPageOfCurations(context)
        .then(response => {
            console.log(response)
            curations.value = response.data.data
            if (totalRows.value !== response.data.meta.total) {
                totalRows.value = response.data.meta.total
            }
            loading.value = false
        })
        .catch((e) => {
            console.log('Error fetching curations:', e)
            loading.value = false
        })
}

const truncateDiseaseEntityNotes = (item) => {
    if (item.disease_entity_notes) {
        let entity = item.disease_entity_notes;
        if (entity.length > 32) {
            entity = entity.substr(0, 32) + '…' // truncate with ellipsis
        }
        return entity
    }

    return null
}

const mondo_iri = (item) => {
    if (item.mondo_id) {
        return `http://purl.obolibrary.org/obo/${item.mondo_id.replace(':', '_')}`
    }
    return null
}

const handlePage = (event) => {
    console.log(event)
    currentPage.value = event.page + 1
    updateCurrentPage()
}

const handleSort = (event) => {
    console.log(event)
    sortBy.value = event.sortField
    sortDesc.value = event.sortOrder === -1
    currentPage.value = 1
    updateCurrentPage()
}

// TODO: filtering is just global for now, could refine to use primevue filtering
watchDebounced(filter, (newValue) => {
    console.log('Filter changed:', newValue)
    currentPage.value = 1
    updateCurrentPage()
}, { debounce: 500 })

onMounted(() =>
    updateCurrentPage()
)

</script>

<style></style>