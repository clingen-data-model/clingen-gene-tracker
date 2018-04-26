<style></style>
<template>
    <div class="component-container">
        <div v-show="phenotypes.length == 0 && !loading">
            <div class="alert alert-secondary clearfix">
                <p>The gene <strong>{{ updatedTopic.value }}</strong> is not associated with a disease entity per OMIM at this time.</p>
            </div>
            <div class="alert alert-info" v-show="message">{{message}}</div>
        </div>
        <div class="row" v-show="phenotypes.length > 0">
            <div class="col-lg-8">
                <b-table 
                    v-show="showTable"
                    :items="phenotypes"
                    :fields="fields"
                    stacked="sm"
                    striped 
                    hover 
                    small
                >
                    <template slot="checkbox" slot-scope="data">
                        <input 
                            class="form-check-input form-check-input-lg"
                            type="checkbox" 
                            v-model="updatedTopic.phenotypes"
                            :value="data.item.phenotypeMimNumber"
                            :disabled="disabled"
                        ></input>
                    </template>
                </b-table>
                
                <div class="alert alert-info" v-show="message">{{message}}</div>

                <div v-show="showRationale">
                    <div class="form-group">
                        <label for="rationale_id">What is your rationale for this curation?</label>
                        <select id="rationale_id" v-model="updatedTopic.rationale_id" class="form-control"></select>
                    </div>
                    <div class="form-group" v-show="showPmids">
                        <label for="pmids">Supporting PMIDS:</label>
                        <input id="pmids" v-model="updatedTopic.pmids" class="form-control"></input>
                    </div>
                    <div class="form-group" v-show="!showPmids">
                        <label for="isolated_phenotype">Enter broader OMIM phenotype (MIM phenotype):</label>
                        <input id="isolated_phenotype" v-model="updatedTopic.isolated_phenotype" class="form-control"></input>
                    </div>
                    <div class="form-group">
                        <label for="rationale_notes">Other comments:</label>
                        <textarea id="rationale_notes" v-model="updatedTopic.rationale_notes" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <criteria-table></criteria-table>
            </div>
        </div>
        <topic-notifications :topic="updatedTopic" class="mt-2"></topic-notifications>
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';
    import CriteriaTable from './../CriteriaTable';
    import TopicNotifications from './ExistingTopicNotification'
    import topicFormMixin from '../../../mixins/topic_form_mixin'
    import  phenotypeListMixin from '../../../mixins/phenotype_list_mixin'

    export default {
        components: {
             CriteriaTable,
             TopicNotifications
        },
        props: ['disabled'],
        mixins: [
            topicFormMixin, // handles syncing of prop value to updatedTopic
            phenotypeListMixin
        ],
        data: function () {
            return {
                showRationale: true,
                showPmids: true,
                phenotypes: [],
                updatedTopic: {},
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
                },
                message: null,
            }
        },
        watch: {
            updatedTopic: function (to, from) {
                if (to.gene_symbol != from.gene_symbol) {
                    this.fetchPhenotypes(this.updatedTopic.gene_symbol)
                        .then((response) => {
                            if (this.phenotypes.length == 0) {
                                this.message = 'There is nothing for you to do on this screen b/c the gene symbole '+this.updatedTopic.gene_symbol+' does not have disease entities associated with it in OMIM'                                
                            }
                            if (this.phenotypes.length == 1 && this.updatedTopic.curation_type_id == 1 && this.updatedTopic.phenotypes.length == 0) {
                                Vue.set(this.updatedTopic.phenotypes, 0, this.phenotypes[0].phenotypeMimNumber)
                                this.message = 'We have preselected the phenotype because you indicated you are curating '+this.updatedTopic.gene_symbol+' with this single disease entity';
                            }

                        })
                }
            }
        },
        computed: {
            loading: function () {
                return this.$store.getters.loading;
            },
            showTable: function () {
                return (this.updatedTopic.curation_type_id != 2 && this.updatedTopic.curation_type_id != 3)
            }
        }
    }
</script>