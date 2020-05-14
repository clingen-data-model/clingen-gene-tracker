<template>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Bulk Lookup</h3>
        </div>
        <div class="card-body">
            <lookup-form 
                v-model="geneSymbols"
                @lookup="search" 
                @getCsv="downloadCsv"
                class="mb-3"
            ></lookup-form>


            <div v-if="results.length > 0">
                <!-- <div class="row">
                    <div class="col-md-3">
                        <h5>Filters:</h5>
                        <filter-control 
                            :items="responseGenes" 
                            title="Genes" 
                            :selectedFilters="filters.gene"
                            @toggle="toggleFilter('gene', $event)"
                            @remove="removeFilter('gene', $event)"
                        ></filter-control>
                        <filter-control 
                            :items="resultsPanels" 
                            title="Expert Panels" 
                            :selectedFilters="filters.expertPanel"
                            @toggle="toggleFilter('expertPanel', $event)"
                            @remove="removeFilter('expertPanel', $event)"
                        ></filter-control>
                        <filter-control 
                            :items="resultsClassifications" 
                            title="Classifications" 
                            :selectedFilters="filters.classification"
                            @toggle="toggleFilter('classification', $event)"
                            @remove="removeFilter('classification', $event)"
                        ></filter-control>
                        <filter-control 
                            :items="resultsStatuses" 
                            title="Statuses" 
                            :selectedFilters="filters.status"
                            @toggle="toggleFilter('status', $event)"
                            @remove="removeFilter('status', $event)"
                        ></filter-control>
                    </div>
                    <div class="col-md-9"> -->
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
                        </b-table>
                    <!-- </div>
                </div> -->
            </div>
        </div>
    </div>
</template>
<script>
import getCurations from '../../resources/curations/get_curations';
import moment from 'moment';
import testGeneSymbols from '../../../../../tests/files/med_gene_symbols';
import LookupForm from './BulkLookup/LookupForm'
import FilterControl from './BulkLookup/FilterControl'

export default {
    components: {
        LookupForm,
        FilterControl
    },
    props: {
        
    },
    data() {
        return {
            geneSymbols: 'BRCA1, TP53,',
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
                    thStyle: {
                        widht: "8rem"
                    }
                },
                {
                    key: 'current_classification.pivot.classification_date',
                    label: 'Class. Date',
                    sortable: true,
                    formatter: value => value ? moment(value).format('MM/DD/YY') : null,
                    thStyle: {
                        widht: "8rem"
                    }
                },
                {
                    key: 'current_status.name',
                    label: 'Status',
                    sortable: true,
                    formatter: value => value ? value.replace(/Curation /, '') : null,
                    thStyle: {
                        width: "8rem"
                    }
                },
                {
                    key: 'current_status.pivot.status_date',
                    label: 'Status Date',
                    sortable: true,
                    formatter: value => value ? moment(value).format('MM/DD/YY') : null,
                    thStyle: {
                        width: "8rem"
                    }
                },
                {
                    key: 'updated_at',
                    label: 'Updated',
                    sortable: true,
                    formatter: value => value ? moment(value).format('MM/DD/YY') : null,
                }
            ],
            loadingResults: false,
            filters: {
                gene: [],
                expertPanel: [],
                classification: [],
                status: []
            }
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
        search() {
            this.loadingResults = true;
            getCurations({'gene_symbol': this.geneSymbols, 'with': 'classifications'})
                .then(response => {
                    this.results = response.data.data
                    return response;
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
            axios.post('/api/bulk-lookup', {'gene_symbol': this.geneSymbols, with: 'classifications'})
                .then(response => {
                    const a = document.createElement('a');
                    a.style.display = "none";
                    document.body.appendChild(a);

                    a.href = window.URL.createObjectURL( new Blob([response.data, { type: 'text/csv' }]));

                    a.setAttribute('download', 'bulk-lookup-results.csv');
                    a.click();

                    document.body.removeChild(a);
                })
        }
    }
}
</script>