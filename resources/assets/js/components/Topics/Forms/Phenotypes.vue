<style></style>
<template>
    <div class="component-container">
        <div v-show="phenotypes.length == 0 && !loading">
            <div class="alert alert-secondary clearfix">
                <p>The gene <strong>{{ updatedTopic.value }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                If you continue with this topic you will have to assign a temporary MonDO ID.
            </div>
        </div>
        <div class="row" v-show="phenotypes.length > 0">
            <div class="col-lg-8">
                <b-table striped hover :items="phenotypes" :fields="fields" stacked="sm" small>
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
            </div>
            <div class="col-lg-4">
                <criteria-table></criteria-table>
            </div>
        </div>
        <topic-notifications :topic="updatedTopic"></topic-notifications>
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
                }
            }
        },
        watch: {
            updatedTopic: function (to, from) {
                if (to != from) {
                    this.fetchPhenotypes(this.updatedTopic.gene_symbol)
                        // .then((response) => {
                        //     if (this.phenotypes.length == 1 && this.updatedTopic.curation_type_id == 1) {
                        //         console.log('preselect only phonetype')
                        //         this.updatedTopic.phenotypes.push(this.phenotypes[0]);
                        //     }
                        // })
                }
            }
        },
        computed: {
            loading: function () {
                return this.$store.getters.loading;
            }
        }
    }
</script>