<style></style>
<template>
    <div class="curation-curation-container">
            <div v-show="phenotypes.length == 0 && !loading">
                <div class="alert alert-secondary clearfix">
                    <p>The gene <strong>{{ updatedCuration.gene_symbol }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <omim-loading></omim-loading>
                    <transition name="fade">
                        <div v-show="phenotypes.length > 0">
                            <b-table striped hover :items="phenotypes" :fields="fields" stacked="sm" small bordered>
                                <template v-slot:cell(phenotype)="data">
                                    <span>{{ data.item.phenotype }}</span>
                                    <span v-if="data.item.obsolete" class="badge badge-warning ml-1">Not in latest OMIM</span>
                                </template>
                            </b-table>
                            <div class="form-group">
                                <label><strong>How would you like to proceed?</strong></label>
                                <b-form-radio-group id="btnradios2"
                                    size="lg"
                                    v-model="updatedCuration.curation_type_id"
                                    :options="options"
                                    stacked
                                    name="radioBtnOutline" />
                                <validation-error :messages="errors.curation_type_id"></validation-error>
                            </div>
                        </div>
                    </transition>
                </div>
                <div class="col-lg-4">
                    <criteria-table></criteria-table>
                </div>
            </div>
    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import phenotypeListMixin from '../../../mixins/phenotype_list_mixin'
    import CriteriaTable from './../CriteriaTable.vue';
    import ValidationError from '../../ValidationError.vue';
    import OmimLoading from '../../OmimLoading.vue'

    export default {
        mixins: [
            curationFormMixin,
            phenotypeListMixin
        ],
        components: {
            CriteriaTable,
            ValidationError,
            OmimLoading,
        },
        data() {
            return {
                page: 'curation-types',
                curationTypes: [],
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
                ]
            }
        },
        watch: {
            updatedCuration: function (to, from) {
                if (to != from) {
                    if (to.gene_symbol != from.gene_symbol || to.curation_type_id != from.curation_type_id) {
                        this.fetchPhenotypes(this.updatedCuration.id);
                    }
                    this.updatedCuration.addingCurationType = 1;
                }
            }
        },
        computed: {
            nonObsoletePhenotypes () {
                return (this.phenotypes || []).filter(p => !p.obsolete)
            },
            options: function () {
                const activePhenotypes = this.nonObsoletePhenotypes
                if (this.phenotypesLoaded && activePhenotypes.length == 0 && this.updatedCuration.curation_type_id === null) {
                    this.updatedCuration.curation_type_id = 2;
                    return [];
                }
                if (activePhenotypes.length == 1) {
                    return this.curationTypes
                            .filter(item => item.name != 'lumped')
                            .map(item => ({text: item.description, value: item.id}))
                }
                return this.curationTypes
                        .map(item => ({text: item.description, value: item.id}))
            }
        },
        methods: {
            fetchCurationTypes() {
                window.axios.get('/api/curation-types')
                    .then((response) => {
                        this.curationTypes = response.data
                    })
            }
        },
        mounted() {
            // if (this.updatedCuration.gene_symbol) {
            //     this.fetchPhenotypes(this.updatedCuration.gene_symbol)
            // }
            this.fetchCurationTypes();
        }
    }
</script>