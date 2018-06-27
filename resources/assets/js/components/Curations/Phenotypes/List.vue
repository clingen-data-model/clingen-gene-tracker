<style scoped>
    .unused {
        color: #aaa;
        /*text-decoration: line-through;*/
    }
</style>
<template>
    <div class="component-container">
        <div v-show="phenotypes.length == 0">
            <div class="alert alert-secondary clearfix">
                The gene {{ geneSymbol }} is not associated with a disease entity per OMIM at this time.
            </div>
        </div>

        <div v-if="usedPhenotypes.length > 0">
            <strong>In this curation</strong>
            <ul>
                <li v-for="phenotype in usedPhenotypes">
                    {{ phenotype.phenotype }}
                </li>
            </ul>
        </div>
        <strong v-else>No phenotypes in this curation</strong>

        <div v-show="unusedPhenotypes.length > 0" class="text-muted">
            <strong>Not in this curation</strong>
            <ul>
                <li v-for="phenotype in unusedPhenotypes">
                    {{ phenotype.phenotype }}
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';

    export default {
        props: ['gene-symbol', 'curation'],
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
        computed: {
            usedPhenotypes: function () {
                if (this.phenotypes.length > 0 && this.curation.phenotypes) {
                    return this.phenotypes.filter(pheno => this.curation.phenotypes.indexOf(pheno.phenotypeMimNumber) > -1)
                }
                return this.phenotypes;
            },
            unusedPhenotypes: function () {
                if (this.phenotypes.length > 0 && this.curation.phenotypes) {
                    return this.phenotypes.filter(pheno => this.curation.phenotypes.indexOf(pheno.phenotypeMimNumber) < 0)
                }
                return this.phenotypes;
            },
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