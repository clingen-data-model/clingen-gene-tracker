<style></style>
<template>
    <div class="component-container">
        <h3>Phenotypes: {{phenotypes.length}}</h3>
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
                    :value="data.item"
                ></input>
            </template>
        </b-table>
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';

    export default {
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
            }
        },
        methods: {
            fetchPhenotypes: function () {
                if (this.geneSymbol) {
                    OmimRepo.gene(this.geneSymbol)
                        .then( response => this.phenotypes = response.data.phenotypes )
                        .catch( error => alert(error) )
                }
            }
        },
        mounted: function () {
            this.fetchPhenotypes();
        }
    }
</script>