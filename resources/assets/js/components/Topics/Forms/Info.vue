<style></style>
<template>
    <div id="topic-info-fields">
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC Gene Symbol"
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
                 <div v-for="msg in errors.gene_symbol">{{msg}}</div>
            </b-form-invalid-feedback>
        </b-form-group>
        <div class="row justify-content-end">
            <div class="col-md-9">
                <topic-notifications :topic="updatedTopic"></topic-notifications>
            </div> 
        </div>
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedTopic.expert_panel_id" :state="expertPanelIdError">
                <option :value="null">Select...</option>
                <option v-for="panel in panels" :value="panel.id">{{panel.name}}</option>
            </b-form-select>
            <b-form-invalid-feedback id="expertPanelIdError">
                <div v-for="msg in errors.expert_panel_id">{{msg}}</div>
            </b-form-invalid-feedback>
        </b-form-group>
    
        <b-form-group horizontal id="expert-panel-select-group" label="Curator" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedTopic.curator_id">
                <option :value="null">Select...</option>
                <option v-for="curator in curators" :value="curator.id">{{curator.name}}</option>
            </b-form-select>
        </b-form-group>
    
        <b-form-group horizontal label="Notes" label-for="notes-field">
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedTopic.notes"></textarea>
        </b-form-group>

        <b-form-group horizontal label="Curation Date" label-for="curation_date">
            <date-field v-model="updatedTopic.curation_date" :readonly="true"></date-field>
        </b-form-group>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import _ from 'lodash'
    import TopicNotifications from './ExistingTopicNotification'
    import DateField from '../../DateField'

    export default {
        name: 'test',
        props: ['value', 'errors'],
        components: {
            TopicNotifications,
            DateField
        },
        data: function () {
            return {
                updatedTopic: {
                    gene_symbol: null
                }
            }
        },
        computed: {
            ...mapGetters('panels', {
                panels: 'Items',
            }),
            ...mapGetters('users', {
                curators: 'getCurators'
            }),
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
            expertPanelIdError: function () {
                return (this.errors && this.errors.expert_panel_id && this.errors.expert_panel_id.length > 0) ? false : null;
            },
        },
        watch: {
            // 'updatedTopic.gene_symbol': function (to, from) {
            //     this.checkTopics();
            // },
            updatedTopic: function (to, from) {
                this.$emit('input', this.updatedTopic);
            },
            value: function () {
                if (this.value != this.updatedTopic) {
                    this.syncValue();
                }
            }
        },
        methods: {
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('users', {
                getAllUsers: 'getAllItems'
            }),
            syncValue: function () {
                if (this.value) {
                    this.updatedTopic = JSON.parse(JSON.stringify(this.value));
                }
            }
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
            this.syncValue();
        }
    }
</script>