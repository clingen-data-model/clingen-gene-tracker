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
                            <th>Status</th>
                            <th>Phenotypes</th>
                            <th>Expert Panel</th>
                            <th>Coordinators</th>
                        </tr>
                    </thead>
                    <tbody v-for="(match, idx) in matchedGenes" :key="idx">
                        <tr>
                            <td>
                                <a :href="'/#/curations/'+match.id" :target="'show-'+match.id">
                                    {{match.gene_symbol}}
                                </a>
                            </td>
                            <td>{{(match.current_status) ? match.current_status.name : 'no status'}}</td>
                            <td>
                                <ul class="mb-0" v-if="match.phenotypes && match.phenotypes.length > 0">
                                    <li v-for="(phenotype, idx) in match.phenotypes" :key="phenotype.mim_number">
                                        <strong v-if="hasMatchingPhenotypes(phenotype)">{{phenotype.name}}</strong>
                                        <span v-if="!hasMatchingPhenotypes(phenotype)">{{phenotype.name}}</span>
                                    </li>
                                </ul>
                            </td>
                            <td>{{match.expert_panel.name}}</td>
                            <td>
                                <ul class="list-unstyled">
                                    <li 
                                        v-for="(coordinator, idx) in match.expert_panel.coordinators" 
                                        :key="idx"
                                    >
                                        <a :href="`mailto:${coordinator.email}`">{{coordinator.name}}</a>
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
    import queryStringFromParams from '../../../http/query_string_from_params';
    import WarningAlert from '../../WarningAlert.vue'

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
                const queryParams = {
                    with: [
                        'phenotypes',
                        'expertPanel',
                        'expertPanel.coordinators'
                    ],
                    gene_symbol: this.curation.gene_symbol
                };
                window.axios.get('/api/curations?'+queryStringFromParams(queryParams))
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