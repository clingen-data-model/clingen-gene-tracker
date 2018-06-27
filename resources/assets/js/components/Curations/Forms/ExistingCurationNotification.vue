<style></style>
<template>
    <div>
        <transition name="fade">
            <div class="alert alert-warning" v-show="matchedCount > 0">
                <div class="clearfix">
                    There are already <strong>{{matchedCount}}</strong> curations in curation or pre-curation with this gene symbol.
                    <button type="button" class="btn btn-sm btn-warning float-right" v-b-toggle.matching-curations-details>Details</button>
                </div>
                <b-collapse id="matching-curations-details" class="mt-2">
                    <table class="table table-striped table-bordered table-small bg-white">
                        <thead>
                            <tr>
                                <th>Gene</th>
                                <th>Expert Panel</th>
                                <th>Status</th>
                                <th>Phenotypes</th>
                            </tr>
                        </thead>
                        <tbody v-for="match in matchedGenes">
                            <tr>
                                <td>{{match.gene_symbol}}</td>
                                <td>{{match.expert_panel.name}}</td>
                                <td>{{(match.current_status) ? match.current_status.name : 'no status'}}</td>
                                <td>
                                    <ul class="list-inline mb-0" v-if="match.phenotypes.length > 0">
                                        <li class="list-inline-item" v-for="(phenotype, idx) in match.phenotypes">
                                            <span v-if="idx != 0">,</span>
                                            <strong v-if="hasMatchingPhenotypes(phenotype)">{{phenotype.mim_number}}*</strong>
                                            <span v-if="!hasMatchingPhenotypes(phenotype)">{{phenotype.mim_number}}</span>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </b-collapse>
            </div>
        </transition>
    </div>
</template>
<script>
    export default {
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
                return this.curation && this.curation.phenotypes && this.curation.phenotypes.indexOf(phenotype.mim_number) > -1
            }            
        }
        //component definition
    }
</script>