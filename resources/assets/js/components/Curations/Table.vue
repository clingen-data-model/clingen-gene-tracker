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
        <q-table flat bordered title="Curations" :rows="curations" :columns="columns" row-key="id">
            <template #body-cell-gene_symbol="props">
                <q-td :props="props">
                    <router-link :id="`show-curation-${props.row.id}-link`" :to="`/curations/${props.row.id}`">
                        {{ props.row.gene_symbol }}
                    </router-link>
                    <div v-if="props.row.hgnc_id">
                        (hgnc: <a
                            :href="`https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:${props.row.hgnc_id}`"
                            target="_blank">
                            {{ props.row.hgnc_id }}
                        </a>)
                    </div>
                </q-td>
            </template>
            <template #body-cell-mode_of_inheritance="props">
                <q-td :props="props">
                    <div v-if="props.row.mode_of_inheritance !== null">
                        <div :title="props.row.mode_of_inheritance.name">
                            {{ props.row.mode_of_inheritance.abbreviation }}
                        </div>
                    </div>
                </q-td>
            </template>
            <template #body-cell-mondo_id="props">
                <q-td :props="props">
                    <div>
                        <div v-if="props.row.mondo_id" :title="props.row.mondo_id">
                            <a :href="mondo_iri(props.row)" target="_blank">
                                {{ props.row.mondo_id }}
                            </a>
                        </div>
                        <div v-if="props.row.disease" :title="props.row.disease.name">
                            ({{ props.row.disease.name }})
                        </div>
                        <div v-if="props.row.disease_entity_notes && !(props.row.mondo_id || props.row.disease)"
                            :title="props.row.disease_entity_notes">
                            {{ truncateDiseaseEntityNotes(props.row) }}
                        </div>
                    </div>
                </q-td>
            </template>
            <template #body-cell-actions="props">
                <q-td :props="props">
                    <div>
                        <router-link v-if="user.canEditCuration(props.row)"
                            :id="'edit-curation-' + props.row.id + '-btn'" class="btn btn-secondary btn-sm"
                            :to="`/curations/${props.row.id}/edit`">
                            Edit
                        </router-link>
                        <delete-button :curation="props.row" class="btn-sm">
                            <span class="fa fa-trash">X</span>
                        </delete-button>
                    </div>
                </q-td>
            </template>
        </q-table>
        <div class="row border-top pt-4">
            <div class="col-md-6">Total Records: {{ totalRows }}</div>
        </div>
    </div>
</template>

<script setup>
import getPageOfCurations from '../../resources/curations/get_page_of_curations'
import DeleteButton from './DeleteButton.vue'
import { ref } from 'vue'

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

const store = useStore()
const user = computed(() => {
    // Access the user from the store
    return store.getters.getUser || {}
})

const filterField = ref(null)
const filter = ref(null)
const currentPage = ref(1)
const pageLength = ref(10)
const sortBy = ref('gene_symbol')
const sortDesc = ref(false)
const totalRows = ref(0)
const curations = ref([])
const loading = ref(false)

const columns = ref([
    {
        name: 'gene_symbol',
        field: 'gene_symbol',
        label: 'Gene Symbol',
        sortable: true,
    },
    {
        name: 'mode_of_inheritance',
        label: 'Mode of Inheritance',
        sortable: true,
    },
    {
        name: 'mondo_id',
        field: row => row.mondo_id,
        label: 'Disease Entity',
        sortable: true,
    },
    {
        name: 'expert_panel',
        field: row => row.expert_panel?.name,
        label: 'Expert Panel',
        sortable: true,
    },
    {
        name: 'curation_status',
        field: row => row.current_status?.name,
        label: 'Curation Status',
        sortable: true,
    },
    {
        name: 'curator',
        field: row => row.curator?.name,
        label: 'Curator',
        sortable: true,
    },
    {
        name: 'current_status',
        field: row => row.current_status?.name,
        label: 'Status',
    },
    {
        name: 'id',
        field: 'id',
        label: 'Precuration ID',
    },
    {
        name: 'actions',
        label: 'Actions',
    }
])

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