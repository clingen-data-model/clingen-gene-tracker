<style></style>
<template>
    <div class="curation-topic-container">
        <div v-show="phenotypes.length == 0 && !loading">
            <div class="alert alert-secondary clearfix">
                <p>The gene <strong>{{ updatedTopic.gene_symbol }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                If you continue with this topic you will have to assign a temporary MonDO ID.
            </div>
        </div>
        <div class="row" v-show="phenotypes.length > 0">
            <div class="col-lg-8">
                <b-table striped hover :items="phenotypes" :fields="fields" stacked="sm" small bordered>
                </b-table>
                <div class="form-group">
                    <label><strong>How would you like to proceed?</strong></label>
                    <b-form-radio-group id="btnradios2"
                        size="lg"
                        v-model="updatedTopic.curation_type_id"
                        :options="options"
                        stacked
                        name="radioBtnOutline" />
                </div>
            </div>
            <div class="col-lg-4">
                <criteria-table></criteria-table>
            </div>
        </div>
        <!-- <topic-notifications :topic="updatedTopic"></topic-notifications> -->
    </div>
</template>
<script>
    import topicFormMixin from '../../../mixins/topic_form_mixin'
    import phenotypeListMixin from '../../../mixins/phenotype_list_mixin'
    import CriteriaTable from './../CriteriaTable';

    export default {
        mixins: [
            topicFormMixin,
            phenotypeListMixin
        ],
        components: {
            CriteriaTable,
        },
        data() {
            return {
                curationTypes: [],
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
                }
            }
        },
        computed: {
            options: function () {
                if (this.phenotypes.length == 0) {

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
            if (this.updatedTopic.gene_symbol) {
                this.fetchPhenotypes(this.updatedTopic.gene_symbol)
            }
            this.fetchCurationTypes();
        }
    }
</script>