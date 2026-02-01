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
        <div class="mb-3" id="new-gene-symbol-group"
            :class="{'error': fieldError('gene_symbol')}"
        >
            <label for="gene-symbol-input">HGNC Gene Symbol</label>
            <input id="gene-symbol-input"
                type="text"
                class="form-control"
                v-model="updatedCuration.gene_symbol"
                required
                placeholder="ATK-1"
                :disabled="hasGdmUuid()"
            >
            <gci-linked-message :curation="updatedCuration" attribute-label="the gene"></gci-linked-message>

            <validation-error :messages="errors.gene_symbol"></validation-error>
        </div>

        <curation-notifications :curation="updatedCuration"></curation-notifications>

        <div class="mb-3" :class="{'error': fieldError('moi_id')}">
            <label for="moi_input">Mode of Inheritance</label>
            <select v-model="updatedCuration.moi_id"
                id="moi_input"
                class="form-control"
                :disabled="hasGdmUuid()"
            >
                <option :value="null">Select...</option>
                <option v-for="moi in mois" :key="moi.id"
                    :value="moi.id"
                >
                    {{`${moi.name} (${moi.hp_id})`}}
                </option>
            </select>
            <validation-error :messages="errors.moi_id"></validation-error>
            <gci-linked-message :curation="updatedCuration" attribute-label="the mode of inheritance"></gci-linked-message>
        </div>

        <div class="mb-3" id="expert-panel-select-group" :class="{'error': fieldError('expert_panel_id')}">
            <label for="expert-panel-select">Gene Curation Expert Panel</label>
            <select
                id="expert-panel-select"
                class="form-control"
                v-model="updatedCuration.expert_panel_id"
                :disabled="!canUpdateExpertPanel"
            >
                <option :value="null">Select...</option>
                <option v-for="panel in panelOptions"
                    :value="panel.id"
                    :key="panel.id"
                >
                    {{panel.name}}
                </option>
            </select>
            <small class="text-muted" v-if="!canUpdateExpertPanel && transferEnabled">
                To change the expert panel use click the "Transfer Curation" button.
            </small>
            <validation-error :messages="errors.expert_panel_id"></validation-error>
        </div>

        <div class="mb-3" id="curator-select-group" :class="{'error': fieldError('curator_id')}">
            <label for="curator-select">Curator</label>
            <select id="curator-select" class="form-control" v-model="updatedCuration.curator_id">
                <option :value="null">Select...</option>
                <option v-for="curator in panelCurators"
                    :key="curator.id"
                    :value="curator.id"
                >
                    {{curator.name}}
                </option>
            </select>
            <validation-error :messages="errors.curator_id"></validation-error>
        </div>

        <div class="mb-3" :class="{'error': fieldError('notes')}">
            <label for="notes-field">Notes</label>
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedCuration.curation_notes"></textarea>
            <validation-error :messages="errors.curation_notes"></validation-error>
        </div>

        <div class="mb-3" v-if="updatedCuration && updatedCuration.curation_statuses">
            <label>Status</label>
            <status-form v-model="updatedCuration" class="mt-1"></status-form>
        </div>
        <br>
        <div class="alert alert-info mt-3" v-if="canEditGdmUuid">
            <h5>
                Advanced Info
                <small class="text-muted float-end"><small>
                    You are seeing this b/c you are a trusted user.
                    <br>
                    Only use these fields if you know what you're doing.
                </small></small>
            </h5>
            <hr>
            <div class="mb-3"
                :class="{'error': fieldError('gdm_uuid')}"
            >
                <label for="gdm_uuid">GCI UUID</label>
                <input
                    id="gdm_uuid"
                    type="text"
                    class="form-control"
                    v-model="updatedCuration.gdm_uuid"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                >
                <small>
                    <a :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`"
                        v-if="updatedCuration.gdm_uuid"
                        target="gci"
                    >
                        GCI Record
                    </a>
                </small>
                <validation-error :messages="errors.gdm_uuid"></validation-error>
            </div>
        </div>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useAppStore } from '../../../stores/app'
    import { useMoisStore } from '../../../stores/mois'
    import { usePanelsStore } from '../../../stores/panels'
    import { useUsersStore } from '../../../stores/users'
    import { useCurationStatusesStore } from '../../../stores/curationStatuses'
    import dayjs from 'dayjs'
    import CurationNotifications from './ExistingCurationNotification.vue'
    import DateField from '../../DateField'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import ValidationError from '../../ValidationError.vue'
    import CurationStatusHistory from '../StatusHistory.vue'
    import StatusForm from './StatusForm.vue'

    export default {
        name: 'CurationInfo',
        mixins: [
            curationFormMixin,
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
                newStatusId: null,
            }
        },
        computed: {
            today: function () {
                return dayjs();
            },
            ...mapState(useAppStore, {user: 'getUser'}),
            ...mapState(useMoisStore, {
                mois: 'Items',
            }),
            ...mapState(usePanelsStore, {
                panels: 'Items',
            }),
            ...mapState(useUsersStore, {
                curators: 'getCurators'
            }),
            ...mapState(useCurationStatusesStore, {
                curationStatuses: 'Items',
            }),
            loading() {
                const appStore = useAppStore()
                return appStore.loading
            },
            transferEnabled() {
                const appStore = useAppStore()
                return appStore.features.transferEnabled
            },
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
                return !Boolean(this.updatedCuration && this.updatedCuration.expert_panel_id && this.updatedCuration.id);
            },
            canEditGdmUuid () {
                if (!this.updatedCuration.expert_panel) {
                    return false;
                }
                return this.user.hasPermission('update curation gdm_uuid')
                    || this.user.isPanelCoordinator(this.updatedCuration.expert_panel)
                    || this.user.canEditPanelCurations(this.updatedCuration.expert_panel)
            }
        },
        methods: {
            handleDateSelected(evt) {
            },
            ...mapActions(usePanelsStore, {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions(useMoisStore, {
                getAllMois: 'getAllItems'
            }),
            ...mapActions(useUsersStore, {
                getAllUsers: 'getAllItems'
            }),
            fieldError (field) {
                return (this.errors && this.errors[field] && this.errors[field].length > 0);
            },
            hasGdmUuid() {
                return this.updatedCuration.gdm_uuid !== null && typeof this.updatedCuration.gdm_uuid !== 'undefined';
            }
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
            this.getAllMois();
        }
    }
</script>
