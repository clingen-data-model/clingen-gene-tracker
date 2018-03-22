<style></style>
<template>
    <div class="component-container">
        <div class="alert alert-secondary clearfix" v-show="loading">Loading...</div>
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
    </div>
</template>
<script>
    import OmimRepo from './../../../repositories/OmimRepository';
    import CriteriaTable from './../CriteriaTable';

    export default {
        components: {
            'criteria-table': CriteriaTable
        },
        props: ['value', 'disabled'],
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
        watch: {
            updatedTopic: function () {
                this.$emit('input', this.updatedTopic);
            },
            value: function () {
                if (this.value != this.updatedTopic) {
                    this.syncValue();
                }
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
            syncValue: function () {
                if (this.value) {
                    this.updatedTopic = JSON.parse(JSON.stringify(this.value));
                    this.fetchPhenotypes()    
                }
            }
        },
        mounted: function () {
            this.fetchPhenotypes();
            this.syncValue();
        }
    }
</script>