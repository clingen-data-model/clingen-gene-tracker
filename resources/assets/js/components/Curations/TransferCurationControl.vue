<template>
    <div>
        <button 
            class="btn btn-sm bg-white border" 
            @click="showTransferForm = true"
        >
            Transfer Curation
        </button>
        <b-modal title="Transfer Curation Ownership" v-model="showTransferForm" :hide-footer="true">
            <div v-if="inGci" class="alert alert-secondary">
                <p>This pre-curation is linked to a record in the GCI you must transfer onwership there.</p>
                <gci-link :curation="curation">Go to the GCI record.</gci-link>
            </div>
            <div v-else>
                <input-row label="Transfer to:" :errors="errors.expert_panel_id">
                    <b-form-select 
                        id="expert-panel-select" 
                        v-model="newExpertPanelId"
                        class="form-control-sm w-75"
                    >
                        <option :value="null">Select...</option>
                        <option v-for="panel in panels" 
                            :value="panel.id"
                            :key="panel.id"
                        >
                            {{panel.name}}
                        </option>
                    </b-form-select>
                </input-row>
                <input-row v-model="startDate" :errors="errors.start_date" label="Start date" type="date"></input-row>
                <!-- <input-row label="">
                    <label>
                        <input type="checkbox" v-model="isHistorical">&nbsp;This is a historical entry
                    </label>
                </input-row>
                <input-row v-model="endDate" :errors="errors.end_date" label="End date" type="date" v-show="isHistorical"></input-row> -->
                <div class="mt-1 border-top pt-3 text-right">
                    <button class="btn btn-secondary" @click="cancel">Cancel</button>
                    <button class="btn btn-primary" @click="transferCuration">Transfer Curation</button>
                </div>
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
            newExpertPanelId: null,
            startDate: null,
            isHistorical: null,
            endDate: null,
            errors: {}
        }
    },
    computed: {
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
        }),
        ...mapActions('curations', {
            updateOwner: 'updateOwner',
        }),
        async transferCuration() {
            try {
                await this.updateOwner({
                    curation: this.curation,
                    expertPanelId: this.newExpertPanelId,
                    startDate: this.startDate
                });
                this.showTransferForm = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
                this.showTransferForm = false;
            }

        },
        cancel() {
            this.showTransferForm = false;
            this.initFormData();
        },
        initFormData() {
            this.newExpertPanelId = null;
            this.startDate = null;
            this.isHistorical = null;
            this.endDate = null;
            this.errors = {};
        },
        fieldError (field) {
            return (this.errors && this.errors[field] && this.errors[field].length > 0);
        },
    },
    mounted() {
        this.getAllPanels();
    }
}
</script>