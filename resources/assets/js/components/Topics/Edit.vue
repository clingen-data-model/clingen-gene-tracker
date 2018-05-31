<style></style>
<template>
    <div>
        <p>
            <router-link to="/topics">
                &lt; Back to topics
            </router-link>
        </p>
        <b-card id="edit-topic-modal">
            <template slot="header">
                <h3>{{ title }}</h3>
            </template>
            <div v-if="!user.canEditTopic(this.topic)" class="alert alert-danger">
                Sorry.  You don't have permission to edit this topic.
            </div>
            <div v-if="topics && user.canEditTopic(this.topic)">
                <b-form id="new-topic-form">
                    <div class="row">
                        <div class="col-md-2 border-right">
                            <nav class="nav flex-column">
                                <router-link 
                                     v-for="(step, idx) in steps"
                                     :key="idx"
                                    :to="$route.path+'#'+idx" 
                                    class="nav-link" 
                                    :class="{active: (currentStep == idx)}"
                                >
                                    {{ step.title }}
                                </router-link>
                            </nav>
                        </div>
                        <div class="col-md-10">
                            <component 
                                :is="currentStep" 
                                :value="updatedTopic"  
                                :errors="errors" 
                                @input="updatedTopic = $event"
                            >
                            </component>                    
                        </div>
                    </div>
                        <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary pull-left" id="topic-proceed" @click="$router.push('/topics')">Cancel</button>
                        </div>
                        <div class="col-md-9 text-right">
                            <b-button variant="default" id="topic-proceed" v-show="" @click="proceed()">Proceed</b-button>
                            <button type="button" class="btn btn-secondary" id="topic" @click="updateTopic()">Save</button>
                            <button type="button" class="btn btn-secondary" id="topic-proceed" @click="updateTopic(exit)">Save &amp; exit</button>
                            <b-button variant="primary" id="new-topic-form-save" @click="updateTopic(navBack)" v-show="currentStepIdx > 0">Back</b-button>
                            <b-button variant="primary" id="new-topic-form-save" @click="updateTopic(navNext)">Next</b-button>
                        </div>
                    </div>
                </b-form>
            </div>
        </b-card>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import CurationType from './Forms/CurationType'
    import Phenotypes from './Forms/Phenotypes'
    import Info from './Forms/Info'
    import Mondo from './Forms/Mondo'

    export default {
        props: ['id'],
        components: {
            Phenotypes,
            Info,
            Mondo,
            CurationType
        },
        data () {
            return {
                user: user,
                currentStep: 'info',
                steps: {
                   info: {
                        title: 'Info',
                        next: 'curation-type'
                    },
                    'curation-type': {
                        title: 'Curation Type',
                        next: 'phenotypes'
                    },
                    phenotypes: {
                        title: 'Phenotypes',
                        next: 'mondo'
                    },
                    mondo: {
                        title: 'MonDO',
                        next: null,
                        back: 'phenotypes' 
                    }

                },
                updatedTopic: {
                    rationals: []
                },
                errors: {},
            }
        },
        watch: {
            $route(to, from) {
                this.setCurrentStep();
            },
            topic: function (to, from) {
                this.setUpdatedTopic(to, from);
            }
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items',
                getTopic: 'getItemById'
            }),            
            title: function () {
                let title = 'Edit Topic: ';
                if (this.topic.gene_symbol) {
                    title += this.topic.gene_symbol
                    if (this.topic.expert_panel) {
                        title += ' for '+this.topic.expert_panel.name
                    }
                }
                return title;
            },
            topic: function(){
                if (this.topics.length == 0) {
                    return {
                        expert_panel: {},
                        rationales: []
                    }
                }

                const topic = this.getTopic(this.id);
                return topic;
            },
            curator: () =>  (this.topic.curator) ? this.topic.curator.name : '--',
            expertPanel: () => { return (this.expert_panel) ? this.topic.expert_panel.name : '--'; },
            selectedPanel: () => {
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
                if (typeof this.steps[this.currentStep].next == 'function') {
                    return this.steps[this.currentStep].next()
                }
                return this.steps[this.currentStep].next
            },
            previousStep: function () {
                if (this.steps[this.currentStep].back) {
                    if (typeof this.steps[this.currentStep].back == 'function') {
                        return this.steps[this.currentStep].back()
                    }
                    return this.steps[this.currentStep].back
                }
                const stepKeys = Object.keys(this.steps);
                if (this.currentStepIdx > 0) {
                    return stepKeys[this.currentStepIdx-1];
                }
                return null
            }

        },
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            }),
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
                        if (callback) {
                            callback(response);
                        }
                        return response;
                    })
                    .catch( (error) => {
                        this.errors = error.response.data.errors;
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
                    this.$router.push(this.$route.path+'#'+this.previousStep)
                }
            },
            exit (response) {
                this.$router.push('/')
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
            },
            setCurrentStep() {
                if (this.$route.hash.substr(1)) {
                    this.currentStep = this.$route.hash.substr(1);
                }
            }
        },
        mounted: function () {
            if (this.topics.length == 0) {
                this.getAllTopics();
            }
            this.updatedTopic = {};
            if (this.topic) {
                this.setUpdatedTopic(this.topic, {})
            }
            this.setCurrentStep();
        }
    }
</script>