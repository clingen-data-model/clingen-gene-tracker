<style></style>
<template>
    <div class="component-container">
        <div class="row">
            <div class="col-lg-8">
                <div class="alert alert-info" v-show="loading && phenotypes.length < 1">Loading phenotype information...</div>
                <div  v-show="!loading || phenotypes.length > 0">
                    <div class="alert alert-secondary clearfix" v-show="phenotypes.length == 0">
                        <p>The gene <strong>{{ updatedTopic.value }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                    </div>

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
                </div>

                <div class="form-group" v-if="showRationale">
                    <label for="rationale_id">What is your rationale for this curation?</label>
                    <b-form-select 
                        id="expert-panel-select" 
                        v-model="updatedTopic.rationales" 
                        :options="rationaleOptions"
                        multiple 
                        :select-size="rationaleOptions.length" 
                    >
                    </b-form-select>
                    <validation-error :messages="errors.rationale_id"></validation-error>
                </div>
                <transition name="fade">
                    <div class="form-group" v-show="updatedTopic.rationale_id == 100">
                        <textarea v-model="updatedTopic.rationale_other" placeholder="Other rationale details" class="form-control"></textarea>
                        <validation-error :messages="errors.rationale_other"></validation-error>
                    </div>
                </transition>
                <div class="form-group" v-show="updatedTopic.curation_type_id != 3">
                    <label for="pmids">Supporting PMIDS:</label>
                    <small>comma separated list</small>
                    <input id="pmids" v-model="updatedTopic.pmids" class="form-control" placeholder="18183754, 123451, 1231231"></input>
                    <validation-error :messages="errors.pmids"></validation-error>
                </div>
                <div class="form-group" v-show="updatedTopic.curation_type_id == 3">
                    <label for="isolated_phenotype">Enter broader OMIM phenotype (MIM phenotype):</label>
                    <input id="isolated_phenotype" v-model="updatedTopic.isolated_phenotype" class="form-control"></input>
                    <validation-error :messages="errors.isolated_phentotype"></validation-error>
                </div>
                <div class="form-group">
                    <label for="rationale_notes">Other comments:</label>
                    <textarea id="rationale_notes" v-model="updatedTopic.rationale_notes" class="form-control"></textarea>
                    <validation-error :messages="errors.rationale_notes"></validation-error>
                </div>
            </div>
            <div class="col-lg-4" v-show="showTable">
                <criteria-table></criteria-table>
            </div>
        </div>
        <topic-notifications :topic="updatedTopic" class="mt-2"></topic-notifications>
    </div>
</template>
<script>
    import { mapGetters } from 'vuex'
    import OmimRepo from './../../../repositories/OmimRepository';
    import CriteriaTable from './../CriteriaTable';
    import TopicNotifications from './ExistingTopicNotification'
    import topicFormMixin from '../../../mixins/topic_form_mixin'
    import phenotypeListMixin from '../../../mixins/phenotype_list_mixin'
    import ValidationError from '../../ValidationError'

    export default {
        components: {
             CriteriaTable,
             TopicNotifications,
             ValidationError
        },
        props: ['disabled'],
        mixins: [
            topicFormMixin, // handles syncing of prop value to updatedTopic
            phenotypeListMixin
        ],
        data: function () {
            return {
                page: 'phenotypes',
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
                            if (this.phenotypes && this.phenotypes.length == 1 && this.updatedTopic.curation_type_id == 1 && this.updatedTopic.phenotypes && this.updatedTopic.phenotypes.length == 0) {
                                Vue.set(this.updatedTopic.phenotypes, 0, this.phenotypes[0].phenotypeMimNumber)
                                this.message = 'We have preselected the phenotype because you indicated you are curating '+this.updatedTopic.gene_symbol+' with this single disease entity';
                            }

                        })
                }
            }
        },
        computed: {
            ...mapGetters('rationales', {
                rationales: 'Items',
            }),
            rationaleOptions: function () {
                let options = this.rationales
                                .filter(item => {
                                    if (this.updatedTopic.curation_type_id != 4 && item.id == 4) {
                                        return false;
                                    }
                                    return true;
                                })
                                .map((item) => {
                                    return {text: item.name, value: item};
                                })
                options.unshift({'value': null, 'text': 'Select...'});
                return options
            },
            showPmids: function () {
                return 
            },
            loading: function () {
                return this.$store.getters.loading;
            },
            showTable: function () {
                return (this.updatedTopic.curation_type_id != 2 && this.updatedTopic.curation_type_id != 3 && this.phenotypes.length > 0)
            },
            showRationale: function () {
                if (this.updatedTopic.curation_type) {
                    if (this.updatedTopic.curation_type.id == 1 && this.phenotypes.length == 1) {
                        return false
                    }
                }
                if (this.phenotypes.length == 0) {
                    return false
                }
                return true;
            }
        }
    }
</script>