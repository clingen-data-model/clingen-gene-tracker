<template>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Bulk Lookup</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <label for="gene-symbol-input">Gene Symbols:</label>
                    <textarea cols="10" rows="10" id="gene-symbol-input" v-model="geneSymbols" class="form-control"></textarea>
                    <!-- <div><small class="text-muted">Comma separated HGNC gene symbols. White space is ignored.</small></div> -->
                    <div class="mt-1">
                        <button @click="geneSymbols = ''" class="btn btn-sm btn-light border">Clear</button>
                        <!-- <button @click="search" class="btn btn-primary btn-sm">Search</button> -->
                        <button @click="search" class="btn btn-primary btn-sm">Search</button>
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="text-right" v-if="results.length > 0">
                        &nbsp;
                        <button @click="downloadCsv" class="btn btn-sm btn-light border">Download CSV</button>
                    </div>
                    <b-table 
                        :fields="fields" 
                        :items="results"
                        primary-key="id"
                        bordered
                        show-empty
                        :empty-text="emptyText"
                        :busy="loadingResults"
                        :small="true"
                    >
                        <div slot="table-busy" class="text-center">
                            Looking for curations...
                        </div>
                    </b-table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import getCurations from '../../resources/curations/get_curations';

export default {
    props: {
        
    },
    data() {
        return {
            geneSymbols: '',
            results: [],
            fields: [
                {
                    key: 'gene_symbol',
                    label: 'Gene',
                    sortable: true
                },
                {
                    key: 'mondo_id',
                    label: 'MonDO ID',
                    sortable: true,
                    thStyle: {
                        width: "9rem"
                    }
                },
                {
                    key: 'mondo_name',
                    label: 'Disease Entity',
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
                    sortable: false,
                    thStyle: {
                        widht: "8rem"
                    }
                },
                {
                    key: 'current_status.name',
                    label: 'Status',
                    // sortable: true,
                    sortable: false,
                    thStyle: {
                        width: "8rem"
                    }
                },
            ],
            loadingResults: false
        }
    },
    computed: {
        emptyText: function () {
            return 'Add comma speparated gene symbols in the textarea to do a bulk lookup';
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
        downloadCsv() {
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