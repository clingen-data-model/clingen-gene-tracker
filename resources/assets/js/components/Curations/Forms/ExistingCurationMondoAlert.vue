<style></style>

<template>
    <div class="component-container">
        <warning-alert v-if="matchedCount > 0">
            <div slot="summary">
                There 
                {{ matchedCount == 1 ? 'is' : 'are' }} 
                already <strong>{{matchedCount}}</strong> 
                curations in curation or pre-curation with this gene symbol and MonDO ID.
            </div>
            <div slot="details">
                <table class="table table-striped table-bordered table-small bg-white">
                    <thead>
                        <tr>
                            <th>Gene</th>
                            <th>Expert Panel</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody v-for="(match, idx) in matchedCurations" :key="idx">
                        <tr>
                            <td>
                                <a :href="'/#/curations/'+match.id" :target="'show-'+match.id">
                                    {{match.gene_symbol}}
                                </a>                                
                            </td>
                            <td>{{match.expert_panel.name}}</td>
                            <td>{{(match.current_status) ? match.current_status.name : 'no status'}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </warning-alert>
    </div>
</template>

<script>
    import WarningAlert from '../../WarningAlert.vue'

    export default {
        components: {
            WarningAlert
        },
        props: ['curation'],
        data() {
            return {
                matchedCurations: []
            }
        },
        computed: {
            matchedCount: function () {
                return this.matchedCurations.length;
            }
        },
        watch: {
            'curation.gene_symbol': function() {
                if (this.curation.gene_symbol) {
                    this.checkCurations();
                }
            },
            'curation.mondo_id': function() {
                if (this.curation && this.curation.mondo_id) {
                    this.checkCurations();
                }
            },
            'curation.disease': function(to) {
                if (this.curation && this.curation.disease) {
                    this.checkCurations();
                }
            }
        },
        methods: {
            checkCurations: _.debounce(function() {
                if ((this.curation && this.curation.gene_symbol) 
                    && this.curation.disease && this.curation.disease.mondo_id
                ) {
                    window.axios.get('/api/curations?gene_symbol='+this.curation.gene_symbol+'&mondo_id='+this.curation.disease.mondo_id)
                        .then((response) => {
                            this.matchedCurations = Object.values(response.data.data).filter((t) => (t.id != this.curation.id));
                        })
                        .catch((error) => {
                            console.error('there was a problem retreiving curations that matched gene_symbol and mondo_id')
                        })
                    return;
                }

                this.matchedCurations = [];
            }, 250)      
        }
    
}
</script>