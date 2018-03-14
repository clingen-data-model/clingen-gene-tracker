<style></style>
<template>
    <div class="new-topic-form-container">
        <pre>{{ updatedTopic.phenotypes }}</pre>
        <b-form id="new-topic-form">
<!--             <select v-model="currentStep">
                <option v-for="(val, key) in steps" :value="key">{{val.title}}</option>
            </select>
            <h4>{{steps[currentStep].title}}</h4>
            <component :is="currentStep" v-bind="currentProps"></component> -->
<!--             <collapsable id="info-fields">
                <div slot="heading">Info</div>
                <div slot="body">
                    <info-fields v-model="updatedTopic" :errors="errors"></info-fields>                    
                </div>
            </collapsable> -->

            <collapsable v-if="updatedTopic.gene_symbol" id="phenotypes-fields">
                <div slot="heading">Phenotypes</div>
                <div slot="body">
                    <phenotype-list v-model="updatedTopic">                        
                    </phenotype-list>
                </div>
            </collapsable>

<!--            <collapsable id="disease-entity-fields">
                <div slot="heading">Disease Entity</div>
                <div slot="body">
                    <disease-entity-fields :errors="errors" :v-model="updatedTopic"></disease-entity-fields>
                </div>
            </collapsable>
 -->            <div class="text-right">
                <hr>
                <b-button variant="default" id="new-topic-form-cancel" @click="cancel()">Cancel</b-button>
                <b-button variant="primary" id="new-topic-form-save" @click="saveTopic()">Save</b-button>
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
        props: ['topic'],
        components: {
            phenotypeList: PhenotypeList,
            infoFields: InfoFields,
            collapsable: Collapsable,
            'disease-entity-fields': DiseaseEntityFields
        },
        data: function () {
            return {
                currentStep: 'info-fields',
                steps: {
                   'info-fields': {
                        title: 'Info'
                    },
                    'phenotype-list': {
                        title: 'Phenotypes'
                    },
                    'disease-entity-fields': {
                        title: 'Disease Entity',
                    }

                },
                updatedTopic: {},
                errors: {},
                viewState: {
                    info: true,
                    phenotypes: true
                }
            }
        },
        watch: {
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
            currentProps: function () {
                return {
                    'v-model': this.updatedTopic,
                    'errors': this.errors
                }
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
                    this.fetchTopic(this.topic.id)
                        .then( function (response) {
                            console.log(this.topic.phenotypes)
                        }.bind(this))
                }
                this.updatedTopic = JSON.parse(JSON.stringify(this.topic));

            },
            saveTopic: function ()
            {
                if (this.updatedTopic.id) {
                    this.updateTopic(this.updatedTopic)
                        .then( (response) => {
                            this.addInfo('Updates saved for '+this.updatedTopic.gene_symbol+' saved for '+this.updatedTopic.expert_panel.name+'.')
                            this.$emit('saved'); 
                            this.clearForm();
                        })
                        .catch( (error) => {
                            this.errors = error.response.data.errors;
                        });
                    return;
                }
                this.createTopic(this.updatedTopic)
                    .then( (response) => {
                        let panel = this.panels.find((item) => item.id == this.updatedTopic.expert_panel_id);
                        this.addInfo('Topic created for gene '+this.updatedTopic.gene_symbol+' saved for '+panel.name+'.')
                        this.$emit('saved'); 
                        return response;
                    })
                    .catch( (error) => {
                        this.errors = error.response.data.errors;
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
