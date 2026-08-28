<style scoped>
    .phenotype {
        color: #666;
        margin-bottom: .5rem;
    }
    .phenotype.curated {
        color: #000;
    }
</style>
<template>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Bulk Curation Lookup</h3>
        </div>
        <div class="card-body">
            <p class="text-grey">
                Look up gene precurations and curations by gene symbol.
            </p>

            <lookup-form 
                v-model="geneSymbols"
                @lookup="search" 
                @getCsv="downloadCsv"
                :errors="formErrors"
                class="mb-3"
            ></lookup-form>


            <div class="alert alert-danger" v-if="formErrors.length > 0">
                <ul class="mb-0">
                    <li v-for="(msg, idx) in formErrors" :key="idx">{{msg}}</li>
                </ul>
            </div>
            <div v-if="notFoundGenes.length > 0" class="alert alert-warning">
                <strong>
                    No curations found for {{ notFoundGenes.length }} {{ notFoundGenes.length === 1 ? 'gene' : 'genes' }}:
                </strong>
                <div class="mt-2">
                    <span v-for="gene in notFoundGenes" :key="gene" class="badge bg-light text-dark border me-1 mb-1">{{ gene }}</span>
                </div>
            </div>
            <div v-if="results.length > 0">
                <h5>Curations:</h5>
                <div class="table-responsive">
                    <b-table 
                        :fields="fields" 
                        :items="filteredResults"
                        primary-key="id"
                        bordered
                        show-empty
                        :empty-text="emptyText"
                        :busy="loadingResults"
                        :small="true"
                        class="text-small"
                        striped
                    >
                        <template #table-busy>
                            <div class="text-center">
                                Looking for curations...
                            </div>
                        </template>
                        <template v-slot:head(available_phenotypes)="data">
                            {{data.label}}
                            <small class="font-weight-normal">(* phenotype is in curation)</small>                        
                        </template>  
                        <template v-slot:cell(available_phenotypes)="{item, value}">
                            <ul class="list-unstyled" style="overflow-x: scroll; word-">
                                <li v-for="ph in (item && item.gene && item.gene.phenotypes ? item.gene.phenotypes : [])" 
                                    :key="ph.mim_number" 
                                    class="phenotype" 
                                    :class="{curated: phenotypeIsInCuration(ph, item)}"
                                >
                                    <span v-if="phenotypeIsInCuration(ph, item)">*</span>{{ph.name}} ({{ph.mim_number}})
                                    <span v-if="ph.label_obsolete" class="badge bg-warning text-dark ms-1">Not in latest OMIM</span>
                                </li>
                            </ul>
                        </template>
                        <template v-slot:cell(current_status.name)="{ item, value }">
                            <div>
                                <span>{{ formatStatus(value, item) }}</span>
                                <span
                                    v-if="item.is_archived"
                                    class="badge bg-warning text-dark ms-2"
                                >
                                    Archived
                                </span>
                            </div>
                        </template>
                    </b-table>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, reactive, ref } from 'vue'
import moment from 'moment'
import LookupForm from './BulkLookup/LookupForm.vue'

const geneSymbols = ref('')
const results = ref([])
const notFoundGenes = ref([])
const fields = [
                {
                    key: 'gene_symbol',
                    label: 'Gene',
                    sortable: true
                },
                {
                    key: 'disease',
                    label: 'Disease Entity',
                    formatter: (value) => {
                        if (!value || !value.name) { return ''; }
                        return value.mondo_id ? `${value.name} (${value.mondo_id})` : value.name;
                    },
                    sortable: true,
                    thStyle: {
                        width: '12rem'
                    }
                },
                {
                    key: 'expert_panel.name',
                    label: 'Expert Panel',
                    sortable: true,
                },
                {
                    key: 'current_classification',
                    label: 'Classification',
                    sortable: true,
                    formatter: function (value) {
                        if (!value) { return ''; }
                        const classification = value.name || '';
                        const classificationDate = value.pivot && value.pivot.classification_date ? moment(value.pivot.classification_date).format('MM/DD/YY') : null;
                        if (classification && classificationDate) { return `${classification} - ${classificationDate}`; }
                        return classification || classificationDate || '';
                    },
                    thStyle: {
                        width: "10rem"
                    }
                },
                {
                    key: 'curation_type.description',
                    label: 'Curation Type',
                    sortable: true,
                    thStyle: {
                        width: "12rem"
                    },
                },
                {
                    key: 'rationales',
                    label: 'Rationales',
                    formatter: function (value) {
                        return Array.isArray(value) ? value.map(r => r.name).filter(Boolean).join(', ') : '';
                    },
                    sortable: false
                },
                {
                    key: 'current_status.name',
                    label: 'Status',
                    sortable: true,
                    thStyle: {
                        width: "10rem"
                    }
                },
                {
                    key: 'updated_at',
                    label: 'Updated',
                    sortable: true,
                    formatter: value => value ? moment(value).format('MM/DD/YY') : null,
                },
                {
                    key: 'available_phenotypes',
                    label: 'Phenotypes',
                    sortable: false
                }
                
]
const loadingResults = ref(false)
const filters = reactive({
    gene: [],
    expertPanel: [],
    classification: [],
    status: []
})
const formErrors = ref([])

const emptyText = computed(() => 'Add comma speparated gene symbols in the textarea to do a bulk lookup')
const filteredResults = computed(() => {
    let filtered = JSON.parse(JSON.stringify(results.value))
    if (filters.gene.length > 0) filtered = filtered.filter(item => filters.gene.indexOf(item.gene_symbol) > -1)
    if (filters.expertPanel.length > 0) filtered = filtered.filter(item => filters.expertPanel.indexOf(item.expert_panel) > -1)
    if (filters.classification.length > 0) filtered = filtered.filter(item => item.current_classification !== null && filters.classification.indexOf(item.current_classification.name) > -1)
    if (filters.status.length > 0) filtered = filtered.filter(item => item.current_status !== null && filters.status.indexOf(item.current_status.name) > -1)
    return filtered
})

function clearResults() {
    results.value = []
    notFoundGenes.value = []
}

function search() {
    formErrors.value = []
    clearResults()
    loadingResults.value = true
    axios.post('/api/bulk-lookup', {'gene_symbol': geneSymbols.value, with: 'classifications'})
        .then(response => {
            results.value = response.data.data || []
            notFoundGenes.value = response.data.meta ? response.data.meta.not_found_genes || [] : []
        })
        .catch(error => {
            const errors = error.response && error.response.data && error.response.data.errors ? error.response.data.errors : {}
            formErrors.value = Object.values(errors).flat()
        })
        .finally(() => {
            loadingResults.value = false
        })
}

function addFilter(key, value) {
    if (Object.keys(filters).indexOf(key) == -1) {
        alert('Bad filter key. Valid filter keys include: "gene", "expertPanel", "classification", and "status"')
        return
    }
    filters[key].push(value)
}

function removeFilter(key, value) {
    const idx = filters[key].indexOf(value)
    if (idx == -1) return
    const list = JSON.parse(JSON.stringify(filters[key]))
    list.splice(idx, 1)
    filters[key] = list
}

function toggleFilter(key, value) {
    if (filters[key].indexOf(value) < 0) addFilter(key, value)
    else removeFilter(key, value)
}

function downloadCsv() {
    search()
    axios.post('/api/bulk-lookup/csv', {'gene_symbol': geneSymbols.value, with: 'classifications'})
        .then(response => {
            const a = document.createElement('a')
            a.style.display = 'none'
            document.body.appendChild(a)
            a.href = window.URL.createObjectURL(new Blob([response.data, { type: 'text/csv' }]))
            a.setAttribute('download', 'bulk-lookup-results.csv')
            a.click()
            document.body.removeChild(a)
        })
        .catch(error => {
            formErrors.value = Object.values(error.response.data.errors).flat()
        })
}

function phenotypeIsInCuration(ph, curation) {
    return curation.phenotypes.map(item => item.mim_number).indexOf(ph.mim_number) > -1
}

function formatStatus(value, item) {
    let display = value || ''
    if (item.current_status_date) display += ` - ${moment(item.current_status_date).format('MM/DD/YY')}`
    return display
}
</script>
