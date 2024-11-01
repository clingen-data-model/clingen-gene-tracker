<script>
    import { mapGetters } from 'vuex'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import phenotypeListMixin from '../../../mixins/phenotype_list_mixin'
    import CriteriaTable from './../CriteriaTable.vue';
    import CurationNotifications from './ExistingCurationNotification.vue'
    import ValidationError from '../../ValidationError.vue'

    export default {
        components: {
             CriteriaTable,
             CurationNotifications,
             ValidationError
        },
        props: ['disabled'],
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
            phenotypeListMixin
        ],
        data: function () {
            return {
                page: 'phenotypes',
                phenotypes: [],
                updatedCuration: {},
                fields: [
                    {
                        key: 'phenotype',
                        sortable: true
                    },
                    {
                        key: 'phenotypeMimNumber',
                        sortable: true
                    },
                    {
                        key: 'phenotypeInheritance',
                        sortable: true,
                        label: 'Inheritance'
                    },
                    {
                        key: 'checkbox',
                        tdClass: 'text-right w-10',
                        sortable: false,
                        label: ' ',
                        formatter: (value, key, item) => {
                            return {
                                'id': item.id,
                                'mim_number': item.phenotypeMimNumber,
                                'name': item.phenotype
                            }
                        }
                    }
                ],
                message: null,
            }
        },
        watch: {
            updatedCuration: function (to, from) {
                if (to.gene_symbol != from.gene_symbol) {
                    this.fetchPhenotypes(this.updatedCuration.id)
                        .then((response) => {
                            if (
                                this.phenotypes?.length === 1 
                                && this.updatedCuration?.curation_type_id === 1 
                                && this.updatedCuration?.phenotypes?.length === 0
                            ) {
                                Vue.set( 
                                    this.updatedCuration.phenotypes, 
                                    0, 
                                    { 
                                        'id': this.phenotypes[0].id,
                                        'mim_number': this.phenotypes[0].phenotypeMimNumber, 
                                        'name': this.phenotypes[0].phenotype 
                                    }
                                );
                                this.message = 'We have preselected the phenotype because you indicated you are curating '+this.updatedCuration.gene_symbol+' with this single disease entity';
                            }

                        })
                }
            }
        },
        computed: {
            ...mapGetters('rationales', {
                rationales: 'Items',
            }),
            showPmids: function () {
                return 
            },
            loading: function () {
                return this.$store.getters.loading;
            },
            showTable: function () {
                // Show table when curation type is single NOT on list.
                return (this.updatedCuration.curation_type_id != 2 && this.updatedCuration.curation_type_id != 3 && this.phenotypes.length > 0)
            },
            showRationale: function () {
                return true;
            }
        }
    }
</script>
<template>
    <div class="component-container">
        <div>
            <div class="alert alert-info" v-show="loading && phenotypes.length < 1">Loading phenotype information...</div>
            <div  v-show="!loading || phenotypes.length > 0">
                <div class="alert alert-secondary clearfix" v-show="phenotypes.length == 0">
                    <p>The gene <strong>{{ updatedCuration.value }}</strong> is not associated with a disease entity per OMIM at this time.</p>
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
                    <template v-slot:head(checkbox)="data">
                        &nbsp;&nbsp;&nbsp;&nbsp;
                    </template>
                    <template v-slot:cell(checkbox)="data">
                        <input 
                            class="form-check-input form-check-input-lg"
                            type="checkbox" 
                            v-model="updatedCuration.phenotypes"
                            :value="data.value"
                            :disabled="disabled"
                        >
                    </template>
                </b-table>
                <curation-notifications :curation="updatedCuration" class="mt-2"></curation-notifications>

                <div class="alert alert-info" v-show="message">{{message}}</div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="form-group" v-if="showRationale">
                    <label for="rationale_id">What is your rationale for this curation?</label>
                    <select v-model="updatedCuration.rationales" 
                        multiple class="form-control" 
                        style="height: 8.5em"
                    >
                        <option v-for="rationale in rationales" :key="rationale.id"
                            :value="rationale"
                        >
                            {{ rationale.name }}
                        </option>
                    </select>
                    <validation-error :messages="errors.rationales"></validation-error>
                </div>
                <transition name="fade">
                    <div class="form-group" v-show="updatedCuration.rationale_id == 100">
                        <textarea v-model="updatedCuration.rationale_other" placeholder="Other rationale details" class="form-control"></textarea>
                        <validation-error :messages="errors.rationale_other"></validation-error>
                    </div>
                </transition>
                <div class="form-group" v-show="updatedCuration.curation_type_id != 3">
                    <label for="pmids">Supporting PMIDS:</label>
                    <small>comma separated list</small>
                    <input id="pmids" v-model="updatedCuration.pmids" class="form-control" placeholder="18183754, 123451, 1231231">
                    <validation-error :messages="errors.pmids"></validation-error>
                </div>
                <div class="form-group" v-show="updatedCuration.curation_type_id == 3">
                    <label for="isolated_phenotype">Enter broader OMIM phenotype (MIM phenotype):</label>
                    <input id="isolated_phenotype" v-model="updatedCuration.isolated_phenotype" class="form-control">
                    <validation-error :messages="errors.isolated_phenotype"></validation-error>
                </div>
                <div class="form-group">
                    <label for="rationale_notes">Provide your Rationale:</label>
                    <textarea id="rationale_notes" v-model="updatedCuration.rationale_notes" class="form-control"></textarea>
                    <validation-error :messages="errors.rationale_notes"></validation-error>
                </div>
            </div>
            <div class="col-lg-4" v-show="showTable">
                <criteria-table></criteria-table>
            </div>
        </div>
    </div>
</template>