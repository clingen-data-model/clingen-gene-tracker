<style scoped>
    .phenotype {
        color: #666;
        margin-bottom: .5rem;
    }
    .phenotype.curated {
        color: #000;
    }
    .phenotypes-table {
        width: 100%;
        table-layout: fixed;
    }
    .phenotypes-table th {
        width: calc(50% - 3rem);
    }
    .phenotypes-table th:first-child {
        width: 6rem;
    }
</style>
<template>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Bulk Gene/Phenotype Lookup</h3>
        </div>
        <div class="card-body">
            <p class="text-grey">
                Look OMIM phenotypes for genes by gene symbol.
            </p>
            <div class="alert alert-danger" v-if="formErrors.length > 0">
                <ul class="mb-0">
                    <li v-for="(msg, idx) in formErrors" :key="idx">{{msg}}</li>
                </ul>
            </div>

            <lookup-form 
                v-model="geneSymbols"
                @lookup="search" 
                @getCsv="downloadCsv"
                class="mb-3"
            ></lookup-form>


            <div v-if="results.length > 0">
                <h5>Curations:</h5>
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
                    <template v-slot:cell(phenotypes)="{value}">
                        <strong v-if="value.length == 0" class="mb-3 d-block">
                            No OMIM phenotypes were found for this gene.
                        </strong>
                        <table class="table phenotypes-table w-100" v-else>
                            <thead>
                                <tr>
                                    <th width="10%">OMIM ID</th>
                                    <th width="45%">Name</th>
                                    <th>MOI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ph, idx) in (value || []).filter(p => !p.label_obsolete)" :key="idx">
                                    <td>{{ph.mim_number}}</td>
                                    <td>{{ph.name}}</td>
                                    <td>{{ph.moi}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </b-table>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, reactive, ref } from 'vue'
import LookupForm from './Curations/BulkLookup/LookupForm.vue'

const geneSymbols = ref('')
const results = ref([])
const fields = [
    { key: 'gene_symbol', label: 'Gene', sortable: true },
    { key: 'phenotypes', label: 'Phenotypes', sortable: false }
]
const loadingResults = ref(false)
const filters = reactive({ gene: [] })
const formErrors = ref([])

const emptyText = computed(() => 'Add comma speparated gene symbols in the textarea to do a bulk lookup')
const filteredResults = computed(() => JSON.parse(JSON.stringify(results.value)))

function requestPayload() {
    return {
        where: {gene_symbol: geneSymbols.value.split(/[, \n]/)},
        with: ['phenotypes']
    }
}

function search() {
    formErrors.value = []
    loadingResults.value = true
    axios.post('/api/genes', requestPayload())
        .then(response => {
            results.value = response.data
            return response
        })
        .catch(error => {
            formErrors.value = Object.values(error.response.data.errors).flat()
        })
        .then(() => {
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
    axios.post('/api/genes/csv', requestPayload())
        .then(response => {
            const a = document.createElement('a')
            a.style.display = 'none'
            document.body.appendChild(a)
            a.href = window.URL.createObjectURL(new Blob([response.data, { type: 'text/csv' }]))
            a.setAttribute('download', 'bulk-gene-lookup-results.csv')
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
</script>
