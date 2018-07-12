<style></style>
<template>
    <div>
        <p>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>
        </p>
        <b-card id="edit-curation-modal">
            <template slot="header">
                <h3>{{ title }}</h3>
            </template>
            <div v-if="!user.canEditCuration(this.curation)" class="alert alert-danger">
                Sorry.  You don't have permission to edit this curation.
            </div>
            <div v-if="curations && user.canEditCuration(this.curation)">
                <b-form id="new-curation-form">
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
                                :value="updatedCuration"  
                                :errors="errors" 
                                @input="updatedCuration = $event"
                            >
                            </component>                    
                        </div>
                    </div>
                        <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary pull-left" id="curation-proceed" @click="$router.push('/curations')">Cancel</button>
                        </div>
                        <div class="col-md-9 text-right">
                            <b-button variant="default" id="curation-proceed" v-show="" @click="proceed()">Proceed</b-button>
                            <button type="button" class="btn btn-secondary" id="curation" @click="updateCuration()">Save</button>
                            <button type="button" class="btn btn-secondary" id="curation-proceed" @click="updateCuration(exit)">Save &amp; exit</button>
                            <b-button variant="primary" id="new-curation-form-save" @click="updateCuration(navBack)" v-show="currentStepIdx > 0">Back</b-button>
                            <b-button variant="primary" id="new-curation-form-save" @click="updateCuration(navNext)">Next</b-button>
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
                updatedCuration: {
                    rationals: []
                },
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
                curations: 'Items',
                getCuration: 'getItemById'
            }),            
            title: function () {
                let title = 'Edit Curation: ';
                if (this.curation.gene_symbol) {
                    title += this.curation.gene_symbol
                    if (this.curation.expert_panel) {
                        title += ' for '+this.curation.expert_panel.name
                    }
                }
                return title;
            },
            curation: function(){
                if (this.curations.length == 0) {
                    return {
                        expert_panel: {},
                        rationales: []
                    }
                }

                const curation = this.getCuration(this.id);
                return curation;
            },
            curator: () =>  (this.curation.curator) ? this.curation.curator.name : '--',
            expertPanel: () => { return (this.expert_panel) ? this.curation.expert_panel.name : '--'; },
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
            ...mapActions('curations', {
                getAllCurations: 'getAllItems'
            }),
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
            setUpdatedCuration: function (to, from) {
                console.log(to);
                console.log(from);
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
        mounted: function () {
            if (this.curations.length == 0) {
                this.getAllCurations();
            }
            this.updatedCuration = {};
            if (this.curation) {
                this.setUpdatedCuration(this.curation, {})
            }
            this.setCurrentStep();
        }
    }
</script>