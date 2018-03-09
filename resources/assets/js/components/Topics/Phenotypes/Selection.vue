<style></style>
<template>
    <div class="component-container">
        <h3>Phenotypes: {{phenotypes.length}}</h3>
        <div class="row">
            <div class="col-sm-8">
                <div v-show="phenotypes.length == 0">
                    <div class="alert alert-secondary clearfix">
                        The gene {{ geneSymbol }} is not associated with a disease entity per OMIM at this time.
                        <button class="btn btn-secondary float-right" @click="">Proceed</button>
                    </div>
                </div>
                <b-table striped hover :items="phenotypes" :fields="fields" stacked="sm" small v-show="phenotypes.length > 0">
                    <template slot="checkbox" slot-scope="data">
                        <input 
                            class="form-check-input form-check-input-lg"
                            type="checkbox" 
                            v-model="selectedPhenotypes"
                            :value="data.item.phenotypeMimNumber"
                        ></input>
                    </template>
                </b-table>
            </div>
            <div class="col-sm-4">
                <criteria-table></criteria-table>
            </div>
        </div>
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';
    import CriteriaTable from './../CriteriaTable';

    export default {
        components: {
            'criteria-table': CriteriaTable
        },
        props: ['gene-symbol', 'value'],
        data: function () {
            return {
                phenotypes: [],
                selectedPhenotypes: [],
                fields: {
                    'phenotype': {
                        sortable: true
                    },
                    'phenotypeMimNumber': {
                        sortable: true
                    },
                    'phenotypeInheritance': {
                        sortable: true,
                        label: 'Inheritance'
                    },
                    'checkbox': {
                        tdClass: 'text-right',
                        sortable: false,
                        label: '&nbsp;&nbsp;&nbsp;',
                    }
                }
            }
        },
        watch: {
            geneSymbol: function (to, from) {
                this.fetchPhenotypes()
            },
            selectedPhenotypes: function () {
                this.$emit('input', this.selectedPhenotypes);
            },
            value: function () {
                if (this.value != this.selectedPhenotypes) {
                    this.syncValue();
                }
            }
        },
        methods: {
            fetchPhenotypes: function () {
                if (this.geneSymbol) {
                    OmimRepo.gene(this.geneSymbol)
                        .then( response => this.phenotypes = response.data.phenotypes )
                        .catch( error => alert(error) )
                }
            },
            syncValue: function () {
                if (this.value) {
                    this.selectedPhenotypes = JSON.parse(JSON.stringify(this.value));
                }
            }
        },
        mounted: function () {
            this.fetchPhenotypes();
            this.syncValue();
        }
    }
</script>