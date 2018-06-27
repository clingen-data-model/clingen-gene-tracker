<style></style>
<template>
    <div class="new-curation-form-container">
        <b-form id="new-curation-form">
            <div class="row">
                <div class="col-md-2 border-right">
                    <ul class="nav nav-pills flex-column text-right">
                        <li class="nav-item" v-for="(step, idx) in steps">
                            <router-link :class="{active: (currentStep == idx)}" :to="$route.path+'#'+idx">
                                {{ step.title }}
                            </router-link>
                        </li>
                    </ul>
                </div>
                <div class="col-md-10">
                    <component :is="currentStep" 
                        :value="updatedCuration" 
                        :errors="errors"
                        @input="updatedCuration = $event"
                    ></component>                    
                </div>
            </div>
                <hr>
            <div class="row">
                <div class="col-md-1">
                    <button type="button" class="btn btn-secondary pull-left" id="curation-proceed" @click="$emit('canceled')">Cancel</button>
                </div>
                <div class="col-md-11 text-right">
                    <div v-if="$route.path == '/curations/create'">
                        <b-button variant="primary" id="create-and-continue-btn" @click="createCuration()">Create curation</b-button>
                    </div>
                    <div v-else>
                        <b-button variant="default" id="curation-proceed" v-show="" @click="proceed()">Proceed</b-button>
                        <button type="button" class="btn btn-secondary" id="curation-proceed" @click="updateCuration(exit)">Save &amp; exit</button>
                        <b-button variant="primary" id="new-curation-form-save" @click="updateCuration(navBack)" v-show="currentStepIdx > 0">Back</b-button>
                        <b-button variant="primary" id="new-curation-form-save" @click="updateCuration(navNext)">Next</b-button>
                    </div>
                </div>
            </div>
        </b-form>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import Collapsable from '../Collapsable'
    import CurationType from './CurationType'
    import DiseaseEntity from './DiseaseEntityFields'
    import Info from './InfoFields'
    import Phenotypes from './Phenotypes/Selection'

    export default {
        props: ['curation'],
        components: {
            Collapsable,
            CurationType,
            DiseaseEntity,
            Info,
            Phenotypes,
        },
        data: function () {
            return {
                currentStep: 'info',
                steps: {
                   info: {
                        title: 'Info',
                        next: 'curation-type'
                    },
                    curation-type: {
                        title: 'Curation Type',
                        next: 'phenotypes'
                    },
                    phenotypes: {
                        title: 'Phenotypes',
                        next: 'disease-entity'
                    },
                    diseaseEntity: {
                        title: 'Disease Entity',
                        next: null
                    }

                },
                updatedCuration: {},
                errors: {},
            }
        },
        watch: {
            $route(to, from) {
                this.setCurrentStep();
            },
            curation: function (to, from) {
                this.setUpdatedCuration(to, from);
            }
        },
        computed: {
            ...mapGetters('curations', {
                getCuration: 'getItemById',                
            }),
            selectedPanel: function () {
                return this.panels.find(
                    obj => { 
                        return obj.id == this.newPanelId 
                    })
            },
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
            currentStepIdx: function () {
                const stepKeys = Object.keys(this.steps);
                return stepKeys.indexOf(this.currentStep);
            },
            nextStep: function () {
                return this.steps[this.currentStep].next;
            },
            previousStep: function () {
                const stepKeys = Object.keys(this.steps);
                if (this.currentStepIdx > 0) {
                    return stepKeys[this.currentStepIdx-1];
                }
                return null
            }
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addAlert'
            ]),
            ...mapActions('curations', {
                fetchCuration: 'fetchItem',
                storeNewItem: 'storeNewItem',
                storeItemUpdates: 'storeItemUpdates'
            }),
            updateCuration (callback) {
                return this.storeItemUpdates(this.updatedCuration)
                    .then( (response) => {
                        this.addInfo('Updates saved for '+this.updatedCuration.gene_symbol+'.')
                        this.$emit('saved');
                        callback(response);
                        return response;
                    })
                    .catch( (error) => {
                        this.errors = error.response.errors;
                        return error;
                    });
            },
            navNext (response) {
                if (this.nextStep) {
                    this.$router.push(this.$route.path+'#'+this.nextStep)
                }
            },
            navBack (response) {
                if (this.previousStep) {
                    this.currentStep = this.previousStep;
                }
            },
            exit (response) {
                this.$router.go(-1)
            },
            createCuration () {
                return this.storeNewItem(this.updatedCuration)
                    .then( (response) => {
                        this.$emit('saved');
                        this.$emit('created');
                        this.addInfo('Curation with '+this.updatedCuration.gene_symbol+' created.')
                        this.$router.push('/curations/'+response.data.data.id+'/edit');
                        return response;
                    })
                    .catch( (error) => {
                        console.log(error);
                        this.errors = error.response.data.errors;
                        return error;
                    })
            },
            setUpdatedCuration: function (to, from) {
                if (to.id != from.id) {
                    this.fetchCuration(this.curation.id);
                }
                this.updatedCuration = JSON.parse(JSON.stringify(this.curation));
            },
            cancel: function ()
            {
                this.$emit('canceled');
                this.clearForm();
            },
            clearForm: function () {
                this.updatedCuration = {};
                this.errors = {}
            },
            proceed: function () {

                this.currentStep = 'disease-entity-fields';
            },
            setCurrentStep() {
                if (this.$route.hash.substr(1)) {
                    this.currentStep = this.$route.hash.substr(1);
                }
            }
        },
        mounted: function() {
            this.updatedCuration = {};
            if (this.curation) {
                this.setUpdatedCuration(this.curation, {})
            }
            this.setCurrentStep();
        }
    }
</script>
