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
    <div id="curation-info-fields" style="position: relative">
        <div v-if="loading" style="position:absolute; top:0; left: 0; bottom: 0; right: 0; background-color: rgba(256, 256, 256, .4); z-index: 10">
            <div class="alert alert-light border text-center" style="margin: auto; width: 10rem; margin-top: 25%">Loading...</div>
        </div>
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC Gene Symbol"
            label-for="gene-symbol-input"
            :class="{'error': fieldError('gene_symbol')}"
        >
            <b-form-input id="gene-symbol-input"
                type="text"
                v-model="updatedCuration.gene_symbol"
                required
                placeholder="ATK-1"
                :disabled="hasGdmUuid() || isArchivedReadOnly"
            >
            </b-form-input>
            <gci-linked-message :curation="updatedCuration" attribute-label="the gene"></gci-linked-message>

            <validation-error :messages="errors.gene_symbol"></validation-error>
        </b-form-group>

        <curation-notifications :curation="updatedCuration"></curation-notifications>

        <b-form-group horizontal label="Mode of Inheritance" label-for="moi_input"
            :class="{'error': fieldError('moi_id')}"
        >
            <b-form-select v-model="updatedCuration.moi_id"
                id="moi_input"
                :disabled="hasGdmUuid() || isArchivedReadOnly"
            >
                <option :value="null">Select...</option>
                <option v-for="moi in mois" :key="moi.id"
                    :value="moi.id"
                >
                    {{`${moi.name} (${moi.hp_id})`}}
                </option>
            </b-form-select>
            <validation-error :messages="errors.moi_id"></validation-error>
            <gci-linked-message :curation="updatedCuration" attribute-label="the mode of inheritance"></gci-linked-message>

        </b-form-group>
        
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select"
            :class="{'error': fieldError('expert_panel_id')}"
        >
            <b-form-select 
                id="expert-panel-select" 
                v-model="updatedCuration.expert_panel_id" 
                :disabled="!canUpdateExpertPanel || isArchivedReadOnly"
            >
                <option :value="null">Select...</option>
                <option v-for="panel in panelOptions" 
                    :value="panel.id"
                    :key="panel.id"
                >
                    {{panel.name}}
                </option>
            </b-form-select>
            <small class="text-muted" v-if="!canUpdateExpertPanel && $store.state.features.transferEnabled">
                To change the expert panel use click the "Transfer Curation" button.
            </small>
            <validation-error :messages="errors.expert_panel_id"></validation-error>
        </b-form-group>
    
        <b-form-group horizontal 
            id="curator-select-group" 
            label="Curator" 
            label-for="curator-select"
            :class="{'error': fieldError('curator_id')}"
        >
            <b-form-select id="curator-select" v-model="updatedCuration.curator_id" :disabled="isArchivedReadOnly">
                <option :value="null">Select...</option>
                <option v-for="curator in panelCurators" 
                    :key="curator.id"
                    :value="curator.id"
                >
                    {{curator.name}}
                </option>
            </b-form-select>
            <validation-error :messages="errors.curator_id"></validation-error>
        </b-form-group>
    
        <b-form-group horizontal label="Notes" label-for="notes-field"
            :class="{'error': fieldError('notes')}"
        >
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedCuration.curation_notes" :disabled="isArchivedReadOnly"></textarea>
            <validation-error :messages="errors.curation_notes"></validation-error>
        </b-form-group>

        <b-form-group horizontal label="Status" label-for="curation_status_id" v-if="updatedCuration && updatedCuration.curation_statuses">
            <status-form v-model="updatedCuration" class="mt-1"></status-form>
        </b-form-group>

        <div v-if="isArchived" class="alert alert-warning mt-3">
            <strong>This curation is archived.</strong>
            <div v-if="updatedCuration.archive_reason" class="mt-1">
                <strong>Reason:</strong> {{ updatedCuration.archive_reason }}
            </div>
            <div v-if="updatedCuration.gcex_url && updatedCuration.gcex_url.startsWith('https://')" class="mt-1">
                <strong>GCEx URL:</strong>
                <a :href="updatedCuration.gcex_url" target="_blank">{{ updatedCuration.gcex_url }}</a>
            </div>
            <div v-if="isArchivedReadOnly" class="mt-2">
                This curation is read-only. If it needs to be updated or archived/unarchived, please contact an administrator.
            </div>
        </div>

        <div v-if="updatedCuration && updatedCuration.id" class="mt-3">
            <div v-if="!canManageArchive" class="alert alert-warning mt-2 mb-0">
                Archiving is managed by administrators. Please contact support if this curation should be archived.
            </div>
            <template v-else>
                <button
                    v-if="!isArchived"
                    type="button"
                    class="btn btn-outline-secondary btn-sm mt-2"
                    @click="toggleArchiveFields"
                >
                    Manage Archive
                </button>
                <button
                    v-else
                    type="button"
                    class="btn btn-secondary mt-2"
                    :disabled="archiveSaving"
                    @click="unarchiveCuration"
                >
                    Unarchive
                </button>
            </template>
        </div>

        <div v-if="showArchiveFields && canManageArchive" class="card mt-3">
            <div v-if="showArchiveGciWarning" class="alert alert-warning mb-3">
                This curation can still be archived in GeneTracker, but the archive update will not be sent to GCI because the record does not currently have a linked GCI UUID or enough identifying data (gene, disease, and mode of inheritance).
            </div>
            <div class="card-body">
                <b-form-group horizontal label="Archive Reason" label-for="archive_reason">
                    <textarea
                        id="archive_reason"
                        class="form-control"
                        v-model="archiveForm.archive_reason"
                        rows="3"
                    ></textarea>
                </b-form-group>

                <b-form-group horizontal label="GCEx URL" label-for="gcex_url">
                    <b-form-input
                        id="gcex_url"
                        v-model="archiveForm.gcex_url"
                        type="text"
                    ></b-form-input>
                </b-form-group>

                <div class="mt-3">
                     <button
                        type="button"
                        class="btn btn-outline-secondary ml-2"
                        :disabled="archiveSaving"
                        @click="toggleArchiveFields"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-warning"
                        :disabled="archiveSaving"
                        @click="archiveCuration"
                    >
                        Archive
                    </button>                    
                </div>
            </div>            
        </div>

        <archived-curation-links
            :value="updatedCuration"
            :editable="!updatedCuration.is_archived && user.canEditCuration(updatedCuration)"
            @input="updatedCuration = $event"
        />

        <br />
        <div class="alert alert-info mt-3" v-if="canEditGdmUuid">
            <h5>
                Advanced Info
                <small class="text-muted float-right"><small>
                    You are seeing this b/c you are a trusted user.
                    <br>
                    Only use these fields if you know what you're doing.
                </small></small>
            </h5>
            <hr>
            <b-form-group
                horizontal 
                label="GCI UUID" 
                label-for="gdm_uuid"
                :class="{'error': fieldError('gdm_uuid')}"
            >
                <b-form-input 
                    id="gdm_uuid" 
                    v-model="updatedCuration.gdm_uuid"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    :disabled="isArchivedReadOnly"
                ></b-form-input>
                <small>
                    <a :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`" 
                        v-if="updatedCuration.gdm_uuid"
                        target="gci"
                    >
                        GCI Record
                    </a>
                </small>
                <validation-error :messages="errors.gdm_uuid"></validation-error>
            </b-form-group>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationNotifications from './ExistingCurationNotification.vue'
    import DateField from '../../DateField'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import ValidationError from '../../ValidationError.vue'
    import StatusForm from './StatusForm.vue'
    import moment from 'moment'
    import ArchivedCurationLinks from './ArchivedCurationLinks.vue'

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
            ArchivedCurationLinks,
        },
        data() {
            return {
                page: 'info',
                newStatusDate: null,
                newStatusId: null,
                showArchiveFields: false,
                archiveForm: {
                    archive_reason: '',
                    gcex_url: '',
                },
                archiveSaving: false,
            }
        },
        watch: {
            updatedCuration: function (to, from) {
                console.log('Info.vue: updatedCuration')
                console.log(from)
                console.log(to)
            },
        },
        computed: {
            today: function () {
                return moment();
            },
            ...mapGetters({ user: 'getUser' }),
            ...mapGetters('mois', {
                mois: 'Items',
            }),
            ...mapGetters('panels', {
                panels: 'Items',
            }),
            ...mapGetters('users', {
                curators: 'getCurators'
            }),
            ...mapGetters('curationStatuses', {
                curationStatuses: 'Items',
            }),
            ...mapGetters({
                loading: 'loading',
            }),
            panelOptions: function () {
                return this.panels.filter(panel => this.user.canSelectExpertPanel(panel)).sort((a, b) => a.name.localeCompare(b.name))
            },
            statusOptions: function () {
                return this.curationStatuses.filter(status => this.user.canSelectCurationStatus(status, this.updatedCuration))
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
            canUpdateExpertPanel() {
                if (this.isArchivedReadOnly) { return false }
                return !Boolean(this.updatedCuration && this.updatedCuration.expert_panel_id && this.updatedCuration.id);
            },
            canEditGdmUuid () {
                if (this.isArchivedReadOnly || !this.updatedCuration.expert_panel) {
                    return false;
                }
                return this.user.hasPermission('update curation gdm_uuid')
                    || this.user.isPanelCoordinator(this.updatedCuration.expert_panel)
                    || this.user.canEditPanelCurations(this.updatedCuration.expert_panel)
            },
            canManageArchive() {
                return this.user && this.user.canManageArchive()
            },
            isArchived() {
                return Boolean(this.updatedCuration && this.updatedCuration.is_archived)
            },
            isArchivedReadOnly() {
                return this.isArchived && !this.canManageArchive
            },
            hasGciIdentification() {
                return Boolean(
                    this.updatedCuration?.gdm_uuid || ( this.updatedCuration?.hgnc_id && this.updatedCuration?.mondo_id && this.updatedCuration?.moi_id)
                )
            },

            showArchiveGciWarning() {
                return this.showArchiveFields && !this.hasGciIdentification
            },
        },
        methods: {
            handleDateSelected(evt) {
            },
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('mois', {
                getAllMois: 'getAllItems'
            }),
            ...mapActions('users', {
                getAllUsers: 'getAllItems'
            }),
            fieldError (field) {
                return (this.errors && this.errors[field] && this.errors[field].length > 0);
            },
            hasGdmUuid() {
                return this.updatedCuration.gdm_uuid !== null && typeof this.updatedCuration.gdm_uuid !== 'undefined';
            },
            toggleArchiveFields() {
                this.showArchiveFields = !this.showArchiveFields
                if (this.showArchiveFields) {
                    this.archiveForm.archive_reason = this.updatedCuration.archive_reason || ''
                    this.archiveForm.gcex_url = this.updatedCuration.gcex_url || ''
                }  else {
                    this.archiveForm.archive_reason = ''
                    this.archiveForm.gcex_url = ''
                }
            },
            async archiveCuration() {
                this.archiveSaving = true
                try {
                    const response = await axios.patch(`/api/curations/${this.updatedCuration.id}/archive`, this.archiveForm)
                    this.updatedCuration = response.data
                    this.showArchiveFields = false
                } finally {
                    this.archiveSaving = false
                }
            },
            async unarchiveCuration() {
                this.archiveSaving = true
                try {
                    const response = await axios.patch(`/api/curations/${this.updatedCuration.id}/unarchive`)
                    this.updatedCuration = response.data
                    this.showArchiveFields = false
                } finally {
                    this.archiveSaving = false
                }
            },
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
            this.getAllMois();
        }
    }
</script>