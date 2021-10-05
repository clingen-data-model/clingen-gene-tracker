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
                    <div slot="table-busy" class="text-center">
                        Looking for curations...
                    </div>
                    <template v-slot:head(available_phenotypes)="data">
                        {{data.label}}
                        <small class="font-weight-normal">(* phenotype is in curation)</small>
                    </template>  
                    <template v-slot:cell(available_phenotypes)="{item, value}">
                        <ul class="list-unstyled" style="overflow-x: scroll; word-">
                            <li v-for="ph in value" 
                                :key="ph.mim_number" 
                                class="phenotype" 
                                :class="{curated: phenotypeIsInCuration(ph, item)}"
                            >
                                <span v-if="phenotypeIsInCuration(ph, item)">*</span>{{ph.name}} ({{ph.mim_number}})
                            </li>
                        </ul>
                    </template>
                </b-table>
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import LookupForm from './BulkLookup/LookupForm'
import FilterControl from './BulkLookup/FilterControl'

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
            fields: [
                {
                    key: 'gene_symbol',
                    label: 'Gene',
                    sortable: true
                },
                {
                    key: 'mondo_name',
                    label: 'Disease Entity',
                    formatter: (value, key, item) => value ? `${item.mondo_name} (${item.mondo_id})` : null,
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
                    key: 'current_classification.name',
                    label: 'Classification',
                    sortable: true,
                    formatter: function (value, key, item) {
                        let disp = value 
                        if (item.current_classification) {
                            disp += ` - ${moment(item.current_classification.pivot.classification_date).format('MM/DD/YY')}`
                        }
                        return disp
                    },
                    thStyle: {
                        width: "10rem"
                    }
                },
                {
                    key: 'current_status.name',
                    label: 'Status',
                    sortable: true,
                    formatter: function (value, key, item) {
                        let disp = value 
                        if (item.current_status_date) {
                            disp += ` - ${moment(item.current_status_date).format('MM/DD/YY')}`
                        }
                        return disp
                    },
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
                
            ],
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
            this.$set(this.filters, key, list);
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
        }
    }
}
</script>