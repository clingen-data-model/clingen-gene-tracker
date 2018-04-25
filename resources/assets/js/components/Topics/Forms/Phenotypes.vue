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

    export default {
        components: {
             CriteriaTable,
             TopicNotifications
        },
        props: ['disabled'],
        mixins: [
            topicFormMixin // handles syncing of prop value to updatedTopic
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
        computed: {
            loading: function () {
                return this.$store.getters.loading;
            }
        },
        methods: {
            fetchPhenotypes: function () {
                if (this.updatedTopic.gene_symbol) {
                    OmimRepo.gene(this.updatedTopic.gene_symbol)
                        .then( response => this.phenotypes = response.data.phenotypes )
                        .catch( error => alert(error) )
                }
            },
        },
        mounted: function () {
            this.fetchPhenotypes();
        }
    }
</script>