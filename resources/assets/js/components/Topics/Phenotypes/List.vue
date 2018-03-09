<style></style>
<template>
    <div class="component-container">
        <h3>Phenotypes</h3>
        <div v-show="phenotypes.length == 0">
            <div class="alert alert-secondary clearfix">
                The gene {{ geneSymbol }} is not associated with a disease entity per OMIM at this time.
                <button class="btn btn-secondary float-right" @click="">Proceed</button>
            </div>
        </div>
        <ul v-show="phenotypes.length > 0">
            <li v-for="phenotype in phenotypes">{{ phenotype.phenotype }}</li>
        </ul>
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';

    export default {
        props: ['gene-symbol'],
        data: function () {
            return {
                phenotypes: []
            }
        },
        watch: {
            geneSymbol: function (to, from) {
                this.fetchPhenotypes()
            }
        },
        methods: {
            fetchPhenotypes: function () {
                if (this.geneSymbol) {
                    OmimRepo.gene(this.geneSymbol)
                        .then(response  => this.phenotypes = response.data.phenotypes)
                        .catch(error => alert(error))
                }
            }
        },
        mounted: function () {
            this.fetchPhenotypes();
        }
    }
</script>