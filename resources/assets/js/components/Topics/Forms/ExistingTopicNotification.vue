<style></style>
<template>
    <div>
        <transition name="fade">
            <div class="alert alert-warning" v-show="matchedCount > 0">
                <div class="clearfix">
                    There are already <strong>{{matchedCount}}</strong> topics in curation or pre-curation with this gene symbol.
                    <button class="btn btn-sm btn-warning float-right" v-b-toggle.matching-topics-details>Details</button>
                </div>
                <b-collapse id="matching-topics-details" class="mt-2">
                    <div class="card mb-3 ml-3" v-for="match in matchedGenes">
                        <div class="card-body">
                            <strong>{{match.gene_symbol}} for {{match.expert_panel.name}}</strong>
                            <div>
                                Phenotypes: 
                                <ul class="list-inline">
                                    <li class="list-inline-item" v-for="phenotype in match.phenotypes">
                                        <strong v-if="hasMatchingPhenotypes(phenotype)">{{phenotype.mim_number}},</strong>
                                        <span v-if="!hasMatchingPhenotypes(phenotype)">{{phenotype.mim_number}},</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </b-collapse>
            </div>
        </transition>
    </div>
</template>
<script>
    export default {
        props: ['topic'],
        data() {
            return {
                matchedGenes: [],
                matchedPhenotypes: []
            }
        },
        watch: {
            'topic.gene_symbol': function() {
                if (this.topic.gene_symbol) {
                    this.checkTopics();
                }
            }
            ,
            'topic.phenotypes': function() {
                if (this.topic && this.topic.phenotypes && this.topic.phenotypes.length > 0) {
                    this.checkTopics();
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
            checkTopics: _.debounce(function() {
                window.axios.get('/api/topics?with=phenotypes&gene_symbol='+this.topic.gene_symbol)
                    .then((response) => {
                        this.matchedGenes = Object.values(response.data.data).filter((t) => (t.id != this.topic.id));
                    })
            }, 500),
            hasMatchingPhenotypes: function (phenotype) {
                return this.topic && this.topic.phenotypes && this.topic.phenotypes.indexOf(phenotype.mim_number) > -1
            }            
        }
        //component definition
    }
</script>