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
                <DataTable
                    :value="filteredResults"
                    :loading="loadingResults"
                    stripedRows
                    showGridlines
                    size="small"
                    class="text-small"
                >
                    <template #empty>
                        {{ emptyText }}
                    </template>
                    <template #loading>
                        Looking for curations...
                    </template>
                    <Column field="gene_symbol" header="Gene" sortable></Column>
                    <Column field="phenotypes" header="Phenotypes">
                        <template #body="{ data }">
                            <strong v-if="data.phenotypes.length == 0" class="mb-3 d-block">
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
                                    <tr v-for="(ph, idx) in data.phenotypes" :key="idx">
                                        <td>{{ph.mim_number}}</td>
                                        <td>{{ph.name}}</td>
                                        <td>{{ph.moi}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
<script>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import LookupForm from './Curations/BulkLookup/LookupForm.vue'

export default {
    components: {
        LookupForm,
        DataTable,
        Column,
    },
    data() {
        return {
            geneSymbols: [],
            results: [],
            loadingResults: false,
            filters: {
                gene: [],
            },
            formErrors: []
        }
    },
    computed: {
        emptyText: function () {
            return 'Add comma speparated gene symbols in the textarea to do a bulk lookup';
        },
        responseGenes: function () {
            return [...new Set(this.results.map(curation => curation.gene_symbol))];
        },
        filteredResults: function () {
            let results = JSON.parse(JSON.stringify(this.results));
            return results
        },
        resultsPanels: function () {
            if (this.results.length == 0)  {
                return [];
            }
            const items = this.results
                .filter(curation => curation.expert_panel !== null)
                .map(curation => {
                    return curation.expert_panel.name
                });

            return [...new Set(items)]
        },
        resultsClassifications: function () {
            if (this.results.length == 0)  {
                return [];
            }
            const items = this.results
                .filter(curation => curation.current_classification !== null)
                .map(curation => {
                    return curation.current_classification.name
                });

            return [...new Set(items)]
        },
        resultsStatuses: function () {
            if (this.results.length == 0)  {
                return [];
            }
            const items = this.results
                .filter(curation => curation.current_status !== null)
                .map(curation => {
                    return curation.current_status ? curation.current_status.name : null
                });

            return [...new Set(items)]
        }
    },
    methods: {
        search() {
            this.formErrors = [];
            this.loadingResults = true;
            axios.post('/api/genes', {
                where: {gene_symbol: this.geneSymbols.split(/[, \n]/)},
                with: [
                    'phenotypes',
                ]
            })
                .then(response => {
                    this.results = response.data
                    return response;
                })
                .catch(error => {
                    const flattenedErrors = Object.values(error.response.data.errors).flat();
                    this.formErrors = flattenedErrors;
                })
                .then(response => {
                    this.loadingResults = false;
                });
        },
        addFilter(key, value) {
            if (Object.keys(this.filters).indexOf(key) == -1) {
                alert('Bad filter key. Valid filter keys include: "gene", "expertPanel", "classification", and "status"');
                return;
            }
            this.filters[key].push(value);
        },
        removeFilter(key, value) {
            const idx = this.filters[key].indexOf(value);
            if (idx == -1) {
                return;
            }
            const list = JSON.parse(JSON.stringify(this.filters[key]));
            list.splice(idx, 1);
            this.filters[key] = list;
        },
        toggleFilter(key, value) {
            if (this.filters[key].indexOf(value) < 0) {
                this.addFilter(key, value);
            } else {
                this.removeFilter(key, value);
            }
        },
        downloadCsv() {
            this.search();
            axios.post('/api/genes/csv', {
                where: {gene_symbol: this.geneSymbols.split(/[, \n]/)},
                with: [
                    'phenotypes',
                ]
            })
                .then(response => {
                    const a = document.createElement('a');
                    a.style.display = "none";
                    document.body.appendChild(a);

                    a.href = window.URL.createObjectURL( new Blob([response.data, { type: 'text/csv' }]));

                    a.setAttribute('download', 'bulk-gene-lookup-results.csv');
                    a.click();

                    document.body.removeChild(a);
                })
                .catch(error => {
                    const flattenedErrors = Object.values(error.response.data.errors).flat();
                    this.formErrors = flattenedErrors;
                })
        },
        phenotypeIsInCuration (ph, curation) {
            return curation.phenotypes.map(i => i.mim_number).indexOf(ph.mim_number) > -1;
        }
    }
}
</script>
