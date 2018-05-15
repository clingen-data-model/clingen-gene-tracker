<style>
    .small-calendar{
        font-size: .8em;
        width: 226px;
    }
    .small-calendar .cell {
        width: 32px;
        height: 32px;
        line-height: 32px;
    }
</style>
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

            <validation-error :messages="errors.gene_symbol"></validation-error>
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
            <validation-error :messages="errors.expert_panel_id"></validation-error>
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

        <b-form-group horizontal label="Status" label-for="topic_status_id" v-if="updatedTopic && updatedTopic.topic_statuses">
            <div class="form-inline">

                <b-form-select id="expert-panel-select" v-model="updatedTopic.topic_status_id">
                    <option :value="null">Select...</option>
                    <option v-for="status in topicStatuses" :value="status.id">{{status.name}}</option>
                </b-form-select>
                &nbsp;
                <datepicker 
                    v-model="updatedTopic.topic_status_timestamp"
                    input-class="form-control"
                    clear-button
                    format='yyyy-MM-dd'
                    calendar-class="small-calendar"
                    placeholder="Select a date"
                    :highlighted="highlighted"
                    ></datepicker>
            </div>
            <topic-status-history :topic="updatedTopic" class="mt-1"></topic-status-history>
        </b-form-group>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import _ from 'lodash'
    import TopicNotifications from './ExistingTopicNotification'
    import DateField from '../../DateField'
    import topicFormMixin from '../../../mixins/topic_form_mixin'
    import ValidationError from '../../ValidationError'
    import TopicStatusHistory from '../StatusHistory'
    import Datepicker from 'vuejs-datepicker'
    import moment from 'moment'

    export default {
        name: 'test',
        mixins: [
            topicFormMixin, // handles syncing of prop value to updatedTopic
        ],
        components: {
            TopicNotifications,
            DateField,
            ValidationError,
            TopicStatusHistory,
            Datepicker
        },
        data() {
            return {
                page: 'info',
                highlighted: {
                    from: new moment().hour(0),
                    to: new moment().hour(24)
                }
            }
        },
        computed: {
            today: function () {
                return moment();
            },
            ...mapGetters('panels', {
                panels: 'Items',
            }),
            ...mapGetters('users', {
                curators: 'getCurators'
            }),
            ...mapGetters('topicStatuses', {
                topicStatuses: 'Items',
            }),
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
            expertPanelIdError: function () {
                return (this.errors && this.errors.expert_panel_id && this.errors.expert_panel_id.length > 0) ? false : null;
            },
        },
        methods: {
            handleDateSelected(evt) {
                console.log(evt);
            },
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('users', {
                getAllUsers: 'getAllItems'
            })
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
        }
    }
</script>