<style></style>
<template>
    <div class="new-topic-form-container">
        <b-form id="new-topic-form">
            <h4>Info</h4>
            <div class="card">
                <div class="card-body">
                    <b-form-group id="new-gene-symbol-group"
                        label="HGNC topic Symbol"
                        label-for="gene-symbol-input"
                    >
                        <b-form-input id="gene-symbol-input"
                        type="text"
                        v-model="updatedTopic.gene_symbol"
                        required
                        placeholder="ATK-1"
                        :state="geneSymbolError">                    
                        </b-form-input>
                        <b-form-invalid-feedback id="geneSymbolError">
                            {{errors.gene_symbol}}
                        </b-form-invalid-feedback>
                    </b-form-group>
                    <b-form-group id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
                        <b-form-select id="expert-panel-select" v-model="updatedTopic.expert_panel_id">
                            <option :value="null">Select...</option>
                            <option v-for="panel in panels" :value="panel.id">{{panel.name}}</option>
                        </b-form-select>
                    </b-form-group>
                    <b-form-group id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
                        <b-form-select id="expert-panel-select" v-model="updatedTopic.curator_id">
                            <option :value="null">Select...</option>
                            <option v-for="curator in curators" :value="curator.id">{{curator.name}}</option>
                        </b-form-select>
                    </b-form-group>
                    <div class="form-group">
                        <label for="notes-field">Notes</label>
                        <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedTopic.notes"></textarea>
                    </div>
                </div>
            </div>

            <phenotype-list 
                v-if="updatedTopic.gene_symbol" 
                :gene-symbol="updatedTopic.gene_symbol"
                v-model="updatedTopic.phenotypes"
            ></phenotype-list>
            <div class="text-right">
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

    export default {
        props: ['topic'],
        components: {
            phenotypeList: PhenotypeList
        },
        data: function () {
            return {
                updatedTopic: {},
                errors: {},
            }
        },
        watch: {
            topic: function (to, from) {
                this.setUpdatedTopic(to, from);
            }
        },
        computed: {
            ...mapGetters('panels', {
                panels: 'Items',
            }),
            ...mapGetters('users', {
                curators: 'getCurators'
            }),
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
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addAlert'
            ]),
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('topics', {
                fetchTopic: 'fetchItem',
                createTopic: 'storeNewItem',
                updateTopic: 'storeItemUpdates'
            }),
            ...mapActions('users', {
                getAllUsers: 'getAllItems'
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

            this.getAllPanels();
            this.getAllUsers();
            this.updatedTopic = {};
            if (this.topic) {
                this.setUpdatedTopic(this.topic, {})
            }
        }
    }
</script>
