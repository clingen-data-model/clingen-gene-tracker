<style scoped>
    .unused {
        color: #aaa;
        /*text-decoration: line-through;*/
    }
</style>
<template>
    <div class="component-container row">
        <div v-if="includedPhenotypes.length > 0" class=" col-lg-8">
            <table class="table table-sm table-xs mb-0">
                <thead>
                    <th>MIM</th>
                    <th style="width: 80%" colspan="2">Phenotype</th>
                </thead>
                <tbody>
                    <tr v-for="phenotype in includedPhenotypes" :key="phenotype.id">
                        <td>{{ phenotype.mim_number }}</td>
                        <td>{{ phenotype.name }}</td>
                        <td>✅</td>
                    </tr>
                </tbody>
                <template v-if="excludedPhenotypes.length > 0">
                    <transition name="fade">
                        <tbody v-show="showExcluded" class="text-muted">
                            <tr v-for="phenotype in excludedPhenotypes" 
                                :key="phenotype.id" 
                            >
                                <td>{{ phenotype.mim_number }}</td>
                                <td>{{ phenotype.name }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <small>
                                        NOTE: Excluded OMIM phenotypes represent those associated with {{curation.gene_symbol}} at the time of pre-curation. 
                                        OMIM phenotypes associated with {{curation.gene_symbol}} may have changed.
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </transition>
                </template>
            </table>
            <button v-if="excludedPhenotypes.length > 0"
                class="text-primary btn btn-sm ml-0 pl-0"
                @click="toggleExcluded">
                {{showExcluded ? `Hide` : `Show`}} excluded phenotypes
            </button>
            <div v-if="excludedPhenotypes.length == 0" class="text-muted"><small><small>All OMIM phenotypes associated with {{curation.gene_symbol}} are included in this curation.</small></small></div>
        </div>
        <div class="col" v-else>
            No phenotypes in this curation
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            geneSymbol: {
                required: true,
            }, 
            curation: {
                required: true,
                type: Object
            },
        },
        data: function () {
            return {
                phenotypes: [],
                showExcluded: false
            }
        },
        computed: {
            includedPhenotypes: function () {
                return this.curation.included_phenotypes;
            },
            excludedPhenotypes: function () {
                return this.curation.excluded_phenotypes;
            },
        },
        methods: {
            toggleExcluded () {
                this.showExcluded = !this.showExcluded;
            }
        }
    }
</script>