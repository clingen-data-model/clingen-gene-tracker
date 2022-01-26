<template>
    <div>
        <!-- <pre>{{curation.expert_panel}}</pre> -->
        <!-- <pre>{{user}}</pre> -->
        <button 
            class="btn btn-sm bg-white border" 
            @click="showTransferForm = true"
            v-if="user.canEditPanelCurations(curation.expert_panel)"
        >
            Transfer Curation
        </button>
        <b-modal title="Transfer Curation Ownership" v-model="showTransferForm" :hide-footer="true" size="lg">
                <!-- <div v-if="inGci" class="alert alert-secondary">
                    <p>This pre-curation is linked to a record in the GCI.  To transfer this record to another expert panel please contact GCI support at <a href="mailto:clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a></p>
                    <gci-link :curation="curation">Go to the GCI record.</gci-link>
                </div>
                <div v-else> -->
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
                    <!-- <input-row label="">
                        <label>
                            <input type="checkbox" v-model="isHistorical">&nbsp;This is a historical entry
                        </label>
                    </input-row>
                    <input-row v-model="endDate" :errors="errors.end_date" label="End date" type="date" v-show="isHistorical"></input-row> -->
                    <div class="mt-1 border-top pt-3 text-right">
                        <button class="btn btn-secondary" @click="cancel">Cancel</button>
                        <button class="btn btn-primary" @click="confirmTransfer()">Transfer Curation</button>
                    </div>
                </div>
        </b-modal>

        <b-modal v-model="showConfirmation" title="Confirm Curation Transfer" :hide-footer="true">
            <div class="alert alert-info">
                <div class="lead">You are about to transfer this curation to {{newExpertPanel.name}}.</div>
                
                Please be sure that you have communicated with the EP coordinator(s) before you continue.
                <ul>
                    <li v-for="coord in newExpertPanel.coordinators" :key="coord.id">
                        {{coord.name}} &lt;<a :href="`mailto:${coord.email}`">{{coord.email}}</a>&gt;
                    </li>
                </ul>
            </div>
            <div class="mt-1 border-top pt-3 text-right">
                <button class="btn btn-secondary" @click="cancel">Cancel</button>
                <button class="btn btn-primary" @click="transferCuration">Transfer Curation</button>
            </div>
        </b-modal>
    </div>
</template>
<script>
import {mapGetters, mapActions} from 'vuex';
import ValidationError from '../ValidationError'
import InputRow from '../forms/InputRow'
import is_validation_error from '../../http/is_validation_error'
import GciLink from '../Curations/GciLink'

export default {
    components: {
        ValidationError,
        InputRow,
        GciLink
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
        ...mapGetters({user: 'getUser'}),
        ...mapGetters('panels', {
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
        ...mapActions('panels', {
            getAllPanels: 'getAllItems',
            getPanel: 'getItem'
        }),
        ...mapActions('curations', {
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