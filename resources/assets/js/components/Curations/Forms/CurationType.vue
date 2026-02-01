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
                            <table class="table table-striped table-sm table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Phenotype</th>
                                        <th>MIM Number</th>
                                        <th>Inheritance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in phenotypes" :key="item.id">
                                        <td>{{ item.phenotype }}</td>
                                        <td>{{ item.phenotypeMimNumber }}</td>
                                        <td>{{ item.phenotypeInheritance }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mb-3">
                                <label><strong>How would you like to proceed?</strong></label>
                                <div v-for="option in options" :key="option.value" class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        :id="'curation-type-'+option.value"
                                        :value="option.value"
                                        v-model="updatedCuration.curation_type_id"
                                        name="curation_type"
                                    >
                                    <label class="form-check-label" :for="'curation-type-'+option.value">
                                        {{ option.text }}
                                    </label>
                                </div>
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
            options: function () {
                if (this.phenotypesLoaded && this.phenotypes.length == 0 && this.updatedCuration.curation_type_id === null) {
                    this.updatedCuration.curation_type_id = 2;
                    return [];
                }
                if (this.phenotypes.length == 1) {
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
            this.fetchCurationTypes();
        }
    }
</script>
