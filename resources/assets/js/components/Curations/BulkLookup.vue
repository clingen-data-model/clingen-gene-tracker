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
                <div v-if="loadingResults" class="text-center">
                    Looking for curations...
                </div>
                <div v-if="filteredResults.length === 0 && !loadingResults" class="text-center text-muted">
                    {{ emptyText }}
                </div>
                <DataTable
                    :value="filteredResults"
                    :loading="loadingResults"
                    stripedRows
                    showGridlines
                    size="small"
                    class="text-small"
                >
                    <Column field="gene_symbol" header="Gene" sortable></Column>
                    <Column field="disease" header="Disease Entity" sortable :style="{ width: '12rem' }">
                        <template #body="{ data }">
                            {{ data.disease ? `${data.disease.name} (${data.disease.mondo_id})` : '' }}
                        </template>
                    </Column>
                    <Column header="Expert Panel" sortable field="expert_panel.name">
                        <template #body="{ data }">
                            {{ data.expert_panel ? data.expert_panel.name : '' }}
                        </template>
                    </Column>
                    <Column header="Classification" sortable field="current_classification.name" :style="{ width: '10rem' }">
                        <template #body="{ data }">
                            <span v-if="data.current_classification">
                                {{ data.current_classification.name }} - {{ $formatDate(data.current_classification.pivot.classification_date, 'MM/DD/YY') }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Curation Type" sortable field="curation_type.description" :style="{ width: '12rem' }">
                        <template #body="{ data }">
                            {{ data.curation_type ? data.curation_type.description : '' }}
                        </template>
                    </Column>
                    <Column header="Rationales" field="rationales">
                        <template #body="{ data }">
                            {{ data.rationales ? data.rationales.map(r => r.name).join(', ') : '' }}
                        </template>
                    </Column>
                    <Column header="Status" sortable field="current_status.name" :style="{ width: '10rem' }">
                        <template #body="{ data }">
                            <span v-if="data.current_status">
                                {{ data.current_status.name }}
                                <span v-if="data.current_status_date"> - {{ $formatDate(data.current_status_date, 'MM/DD/YY') }}</span>
                            </span>
                        </template>
                    </Column>
                    <Column header="Updated" sortable field="updated_at">
                        <template #body="{ data }">
                            {{ data.updated_at ? $formatDate(data.updated_at, 'MM/DD/YY') : '' }}
                        </template>
                    </Column>
                    <Column header="Phenotypes" field="available_phenotypes">
                        <template #header>
                            Phenotypes <small class="fw-normal">(* phenotype is in curation)</small>
                        </template>
                        <template #body="{ data }">
                            <ul class="list-unstyled" style="overflow-x: scroll;" v-if="data.available_phenotypes">
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
import dayjs from 'dayjs';
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import LookupForm from './BulkLookup/LookupForm.vue'
import FilterControl from './BulkLookup/FilterControl.vue'

export default {
    components: {
        LookupForm,
        FilterControl,
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
                expertPanel: [],
                classification: [],
                status: []
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
                    this.formErrors = flattenedErrors;
                })
        },
        phenotypeIsInCuration (ph, curation) {
            return curation.phenotypes.map(i => i.mim_number).indexOf(ph.mim_number) > -1;
        }
    }
}
</script>
