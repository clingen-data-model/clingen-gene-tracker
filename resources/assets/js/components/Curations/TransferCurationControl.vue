<template>
    <div>
        <button class="btn btn-sm bg-white border" @click="showTransferForm = true">
            Transfer Curation Ownership
        </button>
        <b-modal title="Transfer Curation Ownership" v-model="showTransferForm" :hide-footer="true">
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
        </b-modal>
    </div>
</template>
<script>
import {mapGetters} from 'vuex';
import ValidationError from '../ValidationError'
import InputRow from '../forms/InputRow'

export default {
    components: {
        ValidationError,
        InputRow
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

    },
    methods: {
        transferCuration() {
            this.showTransferForm = false;
            alert('transfer curation')
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
        }
    }
}
</script>