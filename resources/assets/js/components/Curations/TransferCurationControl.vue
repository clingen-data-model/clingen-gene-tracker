<template>
    <div>
        <button
            class="btn btn-sm bg-white border"
            @click="showTransferForm = true"
            v-if="user.canEditPanelCurations(curation.expert_panel)"
        >
            Transfer Curation
        </button>
        <Dialog header="Transfer Curation Ownership" v-model:visible="showTransferForm" modal :style="{ width: '50vw' }">
                <div>
                    <div class="alert alert-info">
                        Before transfering this record, be sure that you have contacted the coordinator receiving the curation.
                    </div>
                    <input-row label="Transfer to:" :errors="errors.expert_panel_id">
                        <select
                            id="expert-panel-select"
                            v-model="newExpertPanel"
                            class="form-control form-control-sm w-75"
                        >
                            <option :value="null">Select...</option>
                            <option v-for="panel in filteredPanels"
                                :value="panel"
                                :key="panel.id"
                            >
                                {{panel.name}}
                            </option>
                        </select>
                    </input-row>
                    <input-row v-model="startDate" :errors="errors.start_date" label="Transfer date" type="date"></input-row>
                    <input-row :errors="errors.notes" label="Notes">
                        <textarea class="form-control" cols="60" rows="5" v-model="notes"></textarea>
                    </input-row>
                    <div class="mt-1 border-top pt-3 text-end">
                        <button class="btn btn-secondary" @click="cancel">Cancel</button>
                        <button class="btn btn-primary" @click="confirmTransfer()">Transfer Curation</button>
                    </div>
                </div>
        </Dialog>

        <Dialog v-model:visible="showConfirmation" header="Confirm Curation Transfer" modal>
            <div class="alert alert-info">
                <div class="lead">You are about to transfer this curation to {{newExpertPanel.name}}.</div>

                Please be sure that you have communicated with the EP coordinator(s) before you continue.
                <ul>
                    <li v-for="coord in newExpertPanel.coordinators" :key="coord.id">
                        {{coord.name}} &lt;<a :href="`mailto:${coord.email}`">{{coord.email}}</a>&gt;
                    </li>
                </ul>
            </div>
            <div class="mt-1 border-top pt-3 text-end">
                <button class="btn btn-secondary" @click="cancel">Cancel</button>
                <button class="btn btn-primary" @click="transferCuration">Transfer Curation</button>
            </div>
        </Dialog>
    </div>
</template>
<script>
import { mapState, mapActions } from 'pinia';
import { useAppStore } from '../../stores/app'
import { usePanelsStore } from '../../stores/panels'
import { useCurationsStore } from '../../stores/curations'
import is_validation_error from '../../http/is_validation_error'
import ValidationError from '../ValidationError.vue'
import InputRow from '../forms/InputRow.vue'
import GciLink from '../Curations/GciLink.vue'
import Dialog from 'primevue/dialog'

export default {
    components: {
        ValidationError,
        InputRow,
        GciLink,
        Dialog
    },
    props: {
        curation: {
            type: Object,
            required: true
        }
    },
    emits: [
        'submited',
        'canceled'
    ],
    data() {
        return {
            showTransferForm: false,
            newExpertPanel: {
                coordinators: []
            },
            startDate: null,
            notes: null,
            isHistorical: null,
            endDate: null,
            errors: {},
            showConfirmation: false
        }
    },
    computed: {
        ...mapState(useAppStore, {user: 'getUser'}),
        ...mapState(usePanelsStore, {
            panels: 'Items',
        }),
        filteredPanels() {
            return this.panels.filter(panel => panel.id != this.curation.expert_panel_id);
        },
        inGci() {
            return Boolean(this.curation.gdm_uuid)
        }
    },
    methods: {
        ...mapActions(usePanelsStore, {
            getAllPanels: 'getAllItems',
            getPanel: 'getItem'
        }),
        ...mapActions(useCurationsStore, {
            updateOwner: 'updateOwner',
        }),
        confirmTransfer() {
            this.showConfirmation = true;
        },
        async transferCuration() {
            try {
                await this.updateOwner({
                    curation: this.curation,
                    expertPanelId: this.newExpertPanel.id,
                    startDate: this.startDate,
                    notes: this.notes
                });
                this.showTransferForm = false;
                this.showConfirmation = false;
                this.initFormData()
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
                this.showConfirmation = false;
            }

        },
        cancel() {
            this.showTransferForm = false;
            this.initFormData();
        },
        initFormData() {
            this.newExpertPanel = {coordinators: []};
            this.startDate = null;
            this.isHistorical = null;
            this.endDate = null;
            this.errors = {};
            this.showConfirmation = false;
            this.notes = null;
        },
        fieldError (field) {
            return (this.errors && this.errors[field] && this.errors[field].length > 0);
        },
    },
    mounted() {
        this.getAllPanels({with:['coordinators'], sort: {field: 'name', dir: 'asc'}});
    }
}
</script>
