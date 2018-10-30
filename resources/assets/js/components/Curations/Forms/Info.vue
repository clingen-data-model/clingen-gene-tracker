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
    <div id="curation-info-fields">
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC Gene Symbol"
            label-for="gene-symbol-input"
        >
            <b-form-input id="gene-symbol-input"
                type="text"
                v-model="updatedCuration.gene_symbol"
                required
                placeholder="ATK-1"
                :state="geneSymbolError"> 
            </b-form-input>

            <validation-error :messages="errors.gene_symbol"></validation-error>
        </b-form-group>
        <curation-notifications :curation="updatedCuration"></curation-notifications>
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedCuration.expert_panel_id" :state="expertPanelIdError">
                <option :value="null">Select...</option>
                <option v-for="panel in panelOptions" 
                    :value="panel.id"
                    :key="panel.id"
                >
                    {{panel.name}}
                </option>
            </b-form-select>
            <validation-error :messages="errors.expert_panel_id"></validation-error>
        </b-form-group>
    
        <b-form-group horizontal id="curator-select-group" label="Curator" label-for="curator-select">
            <b-form-select id="curator-select" v-model="updatedCuration.curator_id">
                <option :value="null">Select...</option>
                <option v-for="curator in panelCurators" 
                    :key="curator.id"
                    :value="curator.id"
                >
                    {{curator.name}}
                </option>
            </b-form-select>
        </b-form-group>
    
        <b-form-group horizontal label="Notes" label-for="notes-field">
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedCuration.notes"></textarea>
        </b-form-group>

        <b-form-group horizontal label="Status" label-for="curation_status_id" v-if="updatedCuration && updatedCuration.curation_statuses">
            <status-form v-model="updatedCuration" class="mt-1"></status-form>
        </b-form-group>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import _ from 'lodash'
    import CurationNotifications from './ExistingCurationNotification'
    import DateField from '../../DateField'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import ValidationError from '../../ValidationError'
    import CurationStatusHistory from '../StatusHistory'
    import StatusForm from './StatusForm'
    import moment from 'moment'

    export default {
        name: 'test',
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
        ],
        components: {
            CurationNotifications,
            DateField,
            ValidationError,
            StatusForm,
        },
        data() {
            return {
                page: 'info',
                newStatusDate: null,
                newStatusId: null
            }
        },
        watch: {
            updatedCuration: function (to, from) {
                console.log('Info.vue: updatedCuration')
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
            ...mapGetters('curationStatuses', {
                curationStatuses: 'Items',
            }),
            panelOptions: function () {
                return this.panels.filter(panel => user.canSelectExpertPanel(panel))
            },
            statusOptions: function () {
                return this.curationStatuses.filter(status => user.canSelectCurationStatus(status, this.updatedCuration))
            },
            panelCurators: function () {
                const curators = this.curators.filter(user => {
                    return (
                        user.expert_panels 
                        && user.expert_panels.find(panel => panel.id == this.updatedCuration.expert_panel_id)
                    )
                });

                if (curators && curators.length == 1) {
                    this.updatedCuration.curator_id = curators[0].id
                } else if (curators && curators.length > 0) {
                    this.updatedCuration.curator_id = (this.updatedCuration.curator_id) ? this.updatedCuration.curator_id : null
                } else {
                    this.updatedCuration.curator_id = null;
                }

                return curators;

            },
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