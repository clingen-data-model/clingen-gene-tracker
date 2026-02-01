<style></style>
<template>
    <div>
        <p>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>
        </p>
        <div class="card" id="edit-curation-modal">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h3>{{ title }}</h3>
                    <div class="d-flex space-x-2">
                        <transfer-curation-control
                            :curation="curation"
                             v-if="transferEnabled"
                        ></transfer-curation-control>
                        <router-link :to="'/curations/'+curation.id">
                            view
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div v-if="!curation.id" class="alert alert-info">
                    Loading...
                </div>
                <div v-else-if="!user.canEditCuration(curation)" class="alert alert-danger">
                    Sorry.  You don't have permission to edit this curation.
                </div>
                <div v-if="curations && user.canEditCuration(curation)">
                    <form id="new-curation-form" @submit.prevent>
                        <div class="row">
                            <div class="col-md-2 border-end">
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
                                    :modelValue="updatedCuration"
                                    :errors="errors"
                                    @update:modelValue="updatedCuration = $event"
                                    ref="editPage"
                                >
                                </component>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-secondary" @click="$router.push('/curations')">Cancel</button>
                            </div>
                            <div class="col-md-8 text-end">
                                <button type="button" class="btn btn-secondary" id="curation" @click="updateCuration()">Save</button>
                                <button v-if="nextStep" type="button" class="btn btn-secondary" @click="updateCuration(exit)">Save &amp; exit</button>
                                <button class="btn btn-primary" @click="updateCuration(navBack, 'back')" v-show="currentStepIdx > 0">Back</button>
                                <button class="btn btn-primary" @click="updateCuration(navNext, 'next')">
                                    {{ (!nextStep) ? 'Save and exit' : 'Next'}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useAppStore } from '../../stores/app'
    import { useMessagesStore } from '../../stores/messages'
    import { useCurationsStore } from '../../stores/curations'
    import CurationType from './Forms/CurationType.vue'
    import Phenotypes from './Forms/Phenotypes.vue'
    import DeleteButton from './DeleteButton.vue'
    import Info from './Forms/Info.vue'
    import Mondo from './Forms/Mondo.vue'
    import Classification from './Forms/Classification.vue'
    import Documents from './Forms/Documents.vue'
    import TransferCurationControl from './TransferCurationControl.vue'

    export default {
        props: ['id'],
        components: {
            Phenotypes,
            Info,
            Mondo,
            CurationType,
            DeleteButton,
            Classification,
            Documents,
            TransferCurationControl
        },
        data () {
            return {
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
                        next: 'classification',
                        back: 'phenotypes'
                    },
                    documents: {
                        title: 'Documents',
                        next: null,
                        back: 'classification'
                    }
                },
                updatedCuration: {
                    rationals: []
                },
                standInCuration: {
                    id: 0,
                    expert_panel: {},
                    rationales: []
                },
                errors: {},
            }
        },
        watch: {
            $route(to, from) {
                this.setCurrentStep();
            },
            curation: function (to, from) {
                if (typeof to != 'undefined') {
                    this.setUpdatedCuration(to, from);
                }
            }
        },
        computed: {
            ...mapState(useAppStore, {user: 'getUser'}),
            ...mapState(useCurationsStore, {
                curations: 'Items',
                getCuration: 'getItemById',
            }),
            transferEnabled() {
                const appStore = useAppStore()
                return appStore.features.transferEnabled
            },
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
                    return this.standInCuration
                }

                let curation = this.getCuration(this.id);
                if (!curation) {
                    return this.standInCuration
                }
                return curation;
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
            ...mapActions(useMessagesStore, ['addInfo']),
            ...mapActions(useCurationsStore, {
                fetchCuration: 'fetchItem',
                storeNewItem: 'storeNewItem',
                storeItemUpdates: 'storeItemUpdates'
            }),
            updateCuration (callback, nav) {
                this.updatedCuration.nav = nav;
                return this.storeItemUpdates(this.updatedCuration)
                    .then( (response) => {
                        this.addInfo('Updates saved for '+this.updatedCuration.gene_symbol+'.')
                        this.$emit('saved');
                        if (callback) {
                            callback(response);
                        }
                        this.errors = {};
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
                    return;
                }
                this.$router.push('/');
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
                if (typeof to == 'undefined' ) {
                    return;
                }
                if (typeof from == 'undefined' ) {
                    this.fetchCuration(this.curation.id);
                    this.updatedCuration = JSON.parse(JSON.stringify(this.curation));
                    return;
                }
                if (to.id != from.id && to.id && to.id != 0) {
                    this.fetchCuration(this.curation.id);
                    this.updatedCuration = JSON.parse(JSON.stringify(this.curation));
                    return;
                }
                this.updatedCuration = JSON.parse(JSON.stringify(this.curation));
                return;
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
            this.fetchCuration(this.id);
            this.updatedCuration = {};
            if (this.curation) {
                this.setUpdatedCuration(this.curation, {})
            }
            this.setCurrentStep();
        }
    }
</script>
