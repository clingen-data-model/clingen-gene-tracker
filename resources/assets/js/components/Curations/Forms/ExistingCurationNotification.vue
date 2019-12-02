<style></style>
<template>
    <div>
        <warning-alert v-show="matchedCount > 0">
            <div slot="summary">
                There are already <strong>{{matchedCount}}</strong> curations in curation or pre-curation with this gene symbol.
            </div>
            <div slot="details">
                <table class="table table-striped table-bordered table-small bg-white">
                    <thead>
                        <tr>
                            <th>Gene</th>
                            <th>Expert Panel</th>
                            <th>Status</th>
                            <th>Phenotypes</th>
                        </tr>
                    </thead>
                    <tbody v-for="(match, idx) in matchedGenes" :key="idx">
                        <tr>
                            <td>
                                <a :href="'/#/curations/'+match.id" :target="'show-'+match.id">
                                    {{match.gene_symbol}}
                                </a>
                            </td>
                            <td>{{match.expert_panel.name}}</td>
                            <td>{{(match.current_status) ? match.current_status.name : 'no status'}}</td>
                            <td>
                                <ul class="mb-0" v-if="match.phenotypes.length > 0">
                                    <li v-for="(phenotype, idx) in match.phenotypes" :key="phenotype.mim_number">
                                        <strong v-if="hasMatchingPhenotypes(phenotype)">{{phenotype.name}}</strong>
                                        <span v-if="!hasMatchingPhenotypes(phenotype)">{{phenotype.name}}</span>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </warning-alert>
    </div>
</template>
<script>
    import WarningAlert from '../../WarningAlert'

    export default {
        components: {
            WarningAlert
        },
        props: ['curation'],
        data() {
            return {
                matchedGenes: [],
                matchedPhenotypes: []
            }
        },
        watch: {
            'curation.gene_symbol': function() {
                if (this.curation.gene_symbol) {
                    this.checkCurations();
                }
            }
            ,
            'curation.phenotypes': function() {
                if (this.curation && this.curation.phenotypes && this.curation.phenotypes.length > 0) {
                    this.checkCurations();
                }
            }
        },
        computed: {
            matchedCount: function () {
                const keys = Object.keys(this.matchedGenes)
                return keys.length
            },
        },
        methods: {
            checkCurations: _.debounce(function() {
                window.axios.get('/api/curations?with=phenotypes&gene_symbol='+this.curation.gene_symbol)
                    .then((response) => {
                        this.matchedGenes = Object.values(response.data.data).filter((t) => (t.id != this.curation.id));
                    })
            }, 500),
            hasMatchingPhenotypes: function (phenotype) {
                return this.curation && this.curation.phenotypes && this.curation.phenotypes.map((ph) => ph.mim_number).indexOf(phenotype.mim_number) > -1
            }            
        }
        //component definition
    }
</script>