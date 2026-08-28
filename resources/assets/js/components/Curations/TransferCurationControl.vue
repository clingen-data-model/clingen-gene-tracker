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
            <div v-if="generalError" class="alert alert-danger">
                {{ generalError }}
            </div>
            <div v-if="inGci" class="alert alert-secondary">
                <p>This pre-curation is linked to a record in the GCI.  To transfer this record to another expert panel please contact GCI support at <a href="mailto:clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a></p>
                <gci-link :curation="curation">Go to the GCI record.</gci-link>
            </div>
            <div v-else>
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
                <div class="mt-1 border-top pt-3 text-end">
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
            <div class="mt-1 border-top pt-3 text-end">
                <button class="btn btn-secondary" @click="cancel">Cancel</button>
                <button class="btn btn-primary" @click="transferCuration">Transfer Curation</button>
            </div>
        </b-modal>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import is_validation_error from '../../http/is_validation_error'
import InputRow from '../forms/InputRow.vue'
import GciLink from '../Curations/GciLink.vue'

const props = defineProps({
    curation: {
        type: Object,
        required: true
    }
})
defineEmits(['submited', 'canceled'])

const store = useStore()
const showTransferForm = ref(false)
const newExpertPanel = ref({ coordinators: [] })
const startDate = ref(null)
const notes = ref(null)
const isHistorical = ref(null)
const endDate = ref(null)
const errors = ref({})
const generalError = ref(null)
const showConfirmation = ref(false)

const user = computed(() => store.getters.getUser)
const panels = computed(() => store.getters['panels/Items'])
const filteredPanels = computed(() => {
    return panels.value.filter(panel => panel.id != props.curation.expert_panel_id)
})
const inGci = computed(() => Boolean(props.curation.gdm_uuid))

function confirmTransfer() {
    if (inGci.value) {
        errors.value = {
            curation: ['This pre-curation is linked to a GCI record and cannot be transferred in GT.']
        }
        return
    }
    showConfirmation.value = true
}

async function transferCuration() {
    try {
        await store.dispatch('curations/updateOwner', {
            curation: props.curation,
            expertPanelId: newExpertPanel.value.id,
            startDate: startDate.value,
            notes: notes.value
        })
        showTransferForm.value = false
        showConfirmation.value = false
        initFormData()
    } catch (error) {
        if (is_validation_error(error)) {
            errors.value = error.response.data.errors
        } else {
            generalError.value = error.response?.data?.error || error.response?.data?.message || 'Unable to transfer this curation.'
        }
        showConfirmation.value = false
    }
}

function cancel() {
    showTransferForm.value = false
    initFormData()
}

function initFormData() {
    newExpertPanel.value = { coordinators: [] }
    startDate.value = null
    isHistorical.value = null
    endDate.value = null
    errors.value = {}
    generalError.value = null
    showConfirmation.value = false
    notes.value = null
}

onMounted(() => {
    store.dispatch('panels/getAllItems', {
        with: ['coordinators'],
        sort: { field: 'name', dir: 'asc' }
    })
})
</script>
