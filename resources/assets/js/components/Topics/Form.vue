<style></style>
<template>
    <div class="new-topic-form-container">
        <b-form id="new-topic-form">
            <div class="row">
                <div class="col-md-2 border-right">
                    <ul class="nav nav-pills flex-column text-right">
                        <li class="nav-item" v-for="(step, idx) in steps">
                            <a class="nav-link" 
                                :class="{active: (currentStep == idx)}"
                                :href="$router.currentState" 
                                @click="currentStep = idx"
                            >
                                {{ step.title }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-10">
                    <!-- <h4>{{steps[currentStep].title}}</h4> -->
                    <component :is="currentStep" 
                        :value="updatedTopic" 
                        :errors="errors"
                        @input="updatedTopic = $event"
                    ></component>                    
                </div>
            </div>
                <hr>
            <div class="row">
                <div class="col-md-1">
                    <button type="button" class="btn btn-secondary pull-left" id="topic-proceed" @click="cancel()">Cancel</button>
                </div>
                <div class="col-md-11 text-right">
                    <div v-if="$route.path == '/topics/create'">
                        <b-button variant="primary" id="create-and-continue-btn" @click="createTopic()">Create topic</b-button>
                    </div>
                    <div v-else>
                        <b-button variant="default" id="topic-proceed" v-show="" @click="proceed()">Proceed</b-button>
                        <button type="button" class="btn btn-secondary" id="topic-proceed" @click="updateTopic(exit)">Save &amp; exit</button>
                        <b-button variant="primary" id="new-topic-form-save" @click="updateTopic(navBack)" v-show="currentStepIdx > 0">Back</b-button>
                        <b-button variant="primary" id="new-topic-form-save" @click="updateTopic(navNext)">Next</b-button>
                    </div>
                </div>
            </div>
        </b-form>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import PhenotypeList from './Phenotypes/Selection'
    import InfoFields from './InfoFields'
    import Collapsable from '../Collapsable'
    import DiseaseEntityFields from './DiseaseEntityFields'

    export default {
        props: ['topic', 'step'],
        components: {
            phenotypeList: PhenotypeList,
            infoFields: InfoFields,
            collapsable: Collapsable,
            'disease-entity-fields': DiseaseEntityFields,
        },
        data: function () {
            return {
                currentStep: 'info-fields',
                steps: {
                   'info-fields': {
                        title: 'Info',
                        next: 'phenotype-list'
                    },
                    'phenotype-list': {
                        title: 'Phenotypes',
                        next: 'disease-entity-fields'
                    },
                    'disease-entity-fields': {
                        title: 'Disease Entity',
                        next: null
                    }

                },
                updatedTopic: {},
                errors: {},
            }
        },
        watch: {
            currentStep: function (to, from) {
                this.currentProps;
            },
            topic: function (to, from) {
                this.setUpdatedTopic(to, from);
            }
        },
        computed: {
            ...mapGetters('topics', {
                getTopic: 'getItemById',                
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
            ...mapActions('topics', {
                fetchTopic: 'fetchItem',
                storeNewItem: 'storeNewItem',
                storeItemUpdates: 'storeItemUpdates'
            }),
            updateTopic (callback) {
                return this.storeItemUpdates(this.updatedTopic)
                    .then( (response) => {
                        this.addInfo('Updates saved for '+this.updatedTopic.gene_symbol+'.')
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
                if (this.nextStep === null) {
                    this.$router.push('/topics/'+this.updatedTopic.id)
                }
                this.currentStep = this.nextStep;
            },
            navBack (response) {
                if (this.previousStep) {
                    this.currentStep = this.previousStep;
                }
            },
            exit (response) {
                this.$router.go(-1)
            },
            createTopic () {
                return this.storeNewItem(this.updatedTopic)
                    .then( (response) => {
                        this.$emit('saved');
                        this.$emit('created');
                        this.addInfo('Topic with '+this.updatedTopic.gene_symbol+' created.')
                        this.$router.push('/topics/'+response.data.data.id+'/edit');
                        return response;
                    })
                    .catch( (error) => {
                        console.log(error);
                        this.errors = error.response.data.errors;
                        return error;
                    })
            },
            setUpdatedTopic: function (to, from) {
                if (to.id != from.id) {
                    this.fetchTopic(this.topic.id);
                }
                this.updatedTopic = JSON.parse(JSON.stringify(this.topic));
            },
            cancel: function ()
            {
                this.$emit('canceled');
                this.clearForm();
            },
            clearForm: function () {
                this.updatedTopic = {};
                this.errors = {}
            },
            proceed: function () {

                this.currentStep = 'disease-entity-fields';
            }
        },
        mounted: function() {
            this.updatedTopic = {};
            if (this.topic) {
                this.setUpdatedTopic(this.topic, {})
            }
        }
    }
</script>
