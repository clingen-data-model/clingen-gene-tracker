<style></style>
<template>
    <div class="new-topic-form-container">
        <pre>{{errors}}</pre>
        <b-form id="new-topic-form">
            <select v-model="currentStep">
                <option v-for="(val, key) in steps" :value="key">{{val.title}}</option>
            </select>

            <h4>{{steps[currentStep].title}}</h4>
            <component :is="currentStep" 
                :value="updatedTopic" 
                :errors="errors"
                @input="updatedTopic = $event"
            ></component>

            <div class="row">
                <div class="col-md-1">
                    <button type="button" class="btn btn-secondary pull-left" id="topic-proceed" @click="cancel()">Cancel</button>
                </div>
                <div class="col-md-11 text-right">
                    <b-button variant="default" id="topic-proceed" v-show="" @click="proceed()">Proceed</b-button>
                    <button type="button" class="btn btn-secondary" id="topic-proceed" @click="saveAndExit()">Save &amp; exit</button>
                    <b-button variant="primary" id="new-topic-form-save" @click="saveAndNext()">Next</b-button>
                </div>
            </div>
            <pre>{{ updatedTopic }}</pre>
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
        props: ['topic'],
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
            }
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addAlert'
            ]),
            ...mapActions('topics', {
                fetchTopic: 'fetchItem',
                createTopic: 'storeNewItem',
                updateTopic: 'storeItemUpdates'
            }),
            setUpdatedTopic: function (to, from) {
                if (to.id != from.id) {
                    this.fetchTopic(this.topic.id);
                }
                this.updatedTopic = JSON.parse(JSON.stringify(this.topic));

            },
            saveTopic: function ()
            {
                console.log(this.updatedTopic);
                if (this.updatedTopic.id) {
                    return this.updateTopic(this.updatedTopic)
                        .then( (response) => {
                            this.addInfo('Updates saved for '+this.updatedTopic.gene_symbol+' saved for '+this.updatedTopic.expert_panel.name+'.')
                            this.$emit('saved'); 
                            return response;
                        })
                        .catch( (error) => {
                            this.errors = error.response.data.errors;
                            return error;
                        });
                    return;
                }
                return this.createTopic(this.updatedTopic)
                    .then( (response) => {
                        let panel = this.panels.find((item) => item.id == this.updatedTopic.expert_panel_id);
                        this.addInfo('Topic created for gene '+this.updatedTopic.gene_symbol+' saved for '+panel.name+'.')
                        this.$emit('saved'); 
                        return response;
                    })
                    .catch( (error) => {
                        this.errors = error.response.data.errors;
                        return error;
                    });
            },
            saveAndNext: function () {
                this.saveTopic()
                    .then(response => {
                            this.currentStep = this.steps[this.currentStep].next;
                    });

            },
            saveAndExit: function () {
                this.saveTopic()
                    .then(response => {
                        if (this.errors.length == 0) {
                            this.currentStep = this.steps[this.currentStep].next;
                            this.clearForm();
                            this.$emit('save-exited');
                        }
                    })
                    .catch( function (error) {
                        console.log('caught errors');
                        this.errors = error.response.data.errors;
                        return error;
                    });
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
