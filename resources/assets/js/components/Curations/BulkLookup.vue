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
            <div v-if="results.length > 0">
                <h5>Curations:</h5>
                <DataTable
                    :value="filteredResults"
                    :loading="loadingResults"
                    :small="true"
                    class="text-small"
                    :emptyMessage="emptyText"
                    stripedRows
                    bordered
                >
                    <Column field="gene_symbol" header="Gene" :sortable="true"></Column>
                    <Column field="disease" header="Disease Entity" :sortable="true" headerStyle="width: 12rem">
                        <template #body="{data}">
                            {{ data.disease ? `${data.disease.name} (${data.disease.mondo_id})` : null }}
                        </template>
                    </Column>
                    <Column field="expert_panel" header="Expert Panel" :sortable="true">
                        <template #body="{data}">{{ data.expert_panel ? data.expert_panel.name : null }}</template>
                    </Column>
                    <Column field="current_classification" header="Classification" :sortable="true" headerStyle="width: 10rem">
                        <template #body="{data}">
                            <span v-if="data.current_classification">
                                {{ data.current_classification.name }}
                                <span v-if="data.current_classification.pivot"> - {{ moment(data.current_classification.pivot.classification_date).format('MM/DD/YY') }}</span>
                            </span>
                        </template>
                    </Column>
                    <Column field="curation_type" header="Curation Type" :sortable="true" headerStyle="width: 12rem">
                        <template #body="{data}">{{ data.curation_type ? data.curation_type.description : null }}</template>
                    </Column>
                    <Column field="rationales" header="Rationales">
                        <template #body="{data}">{{ data.rationales.map(r => r.name).join(', ') }}</template>
                    </Column>
                    <Column field="current_status" header="Status" :sortable="true" headerStyle="width: 10rem">
                        <template #body="{data}">
                            <span v-if="data.current_status">
                                {{ data.current_status.name }}
                                <span v-if="data.current_status_date"> - {{ moment(data.current_status_date).format('MM/DD/YY') }}</span>
                            </span>
                        </template>
                    </Column>
                    <Column field="updated_at" header="Updated" :sortable="true">
                        <template #body="{data}">{{ data.updated_at ? moment(data.updated_at).format('MM/DD/YY') : null }}</template>
                    </Column>
                    <Column field="available_phenotypes" header="Phenotypes">
                        <template #header>
                            Phenotypes <small class="font-weight-normal">(* phenotype is in curation)</small>
                        </template>
                        <template #body="{data}">
                            <ul class="list-unstyled" style="overflow-x: scroll">
                                <li v-for="ph in data.available_phenotypes"
                                    :key="ph.mim_number"
                                    class="phenotype"
                                    :class="{curated: phenotypeIsInCuration(ph, data)}"
                                >
                                    <span v-if="phenotypeIsInCuration(ph, data)">*</span>{{ph.name}} ({{ph.mim_number}})
                                </li>
                            </ul>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import LookupForm from './BulkLookup/LookupForm.vue'
import FilterControl from './BulkLookup/FilterControl.vue'

export default {
    components: {
        LookupForm,
        FilterControl,
    },
    props: {

    },
    data() {
        return {
            geneSymbols: [],
            results: [],
            loadingResults: false,
            filters: {
                gene: [],
                expertPanel: [],
                classification: [],
                status: []
            },
            formErrors: []
        }
    },
    computed: {
        emptyText: function () {
            return 'Add comma separated gene symbols in the textarea to do a bulk lookup';
        },
        responseGenes: function () {
            return [...new Set(this.results.map(curation => curation.gene_symbol))];
        },
        filteredResults: function () {
            let results = JSON.parse(JSON.stringify(this.results));
            if (this.filters.gene.length > 0) {
                results = results.filter(item => this.filters.gene.indexOf(item.gene_symbol) > -1)
            }
            if (this.filters.expertPanel.length > 0) {
                results = results.filter(item => this.filters.expertPanel.indexOf(item.expert_panel) > -1)
            }
            if (this.filters.classification.length > 0) {
                results = results.filter(item => item.current_classification !== null && this.filters.classification.indexOf(item.current_classification.name) > -1)
            }
            if (this.filters.status.length > 0) {
                results = results.filter(item => item.current_status !== null && this.filters.status.indexOf(item.current_status.name) > -1)
            }
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
        clearResults () {
            this.results = []
        },
        search() {
            this.formErrors = [];
            this.clearResults();
            this.loadingResults = true;
            axios.post('/api/bulk-lookup', {'gene_symbol': this.geneSymbols, with: 'classifications'})
                .then(response => {
                    this.results = response.data.data
                    return response;
                })
                .catch(error => {
                    const flattenedErrors = Object.values(error.response.data.errors).flat();
                    console.log(flattenedErrors);
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
            console.log(list);
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
            axios.post('/api/bulk-lookup/csv', {'gene_symbol': this.geneSymbols, with: 'classifications'})
                .then(response => {
                    const a = document.createElement('a');
                    a.style.display = "none";
                    document.body.appendChild(a);

                    a.href = window.URL.createObjectURL( new Blob([response.data, { type: 'text/csv' }]));

                    a.setAttribute('download', 'bulk-lookup-results.csv');
                    a.click();

                    document.body.removeChild(a);
                })
                .catch(error => {
                    const flattenedErrors = Object.values(error.response.data.errors).flat();
                    console.log(flattenedErrors);
                    this.formErrors = flattenedErrors;
                })
        },
        phenotypeIsInCuration (ph, curation) {
            return curation.phenotypes.map(i => i.mim_number).indexOf(ph.mim_number) > -1;
        },
        moment,
    }
}
</script>
