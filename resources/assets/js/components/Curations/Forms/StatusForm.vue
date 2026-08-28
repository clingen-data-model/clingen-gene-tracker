<style></style>

<template>
    <div>
        <b-button 
            variant="info"
            size="sm" 
            class="form-control mb-2"
            @click="modalVisible = true"
        >Add or update status</b-button>

        <CurationStatusHistory :curation="modelValue"></CurationStatusHistory>

        <b-modal 
            v-model="modalVisible"
            @hide="submitAll"
        >
            <template #modal-header>
                <div>
                    <h3>Update Curation Status</h3>
                </div>
            </template>
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <b-form-select id="expert-panel-select" v-model="newStatusId">
                                <option :value="null">Select...</option>
                                <option v-for="status in statusOptions"
                                    :key="status.id"
                                    :value="status.id"
                                >
                                    {{status.name}}
                                </option>
                            </b-form-select>
                            <div class="text-danger" v-if="errors.curation_status_id">
                                <div v-for="message in errors.curation_status_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="d-flex align-items-center">
                            <div class="flex-grow-1 me-2">
                                <date-input 
                                    v-model="newStatusDate"
                                ></date-input>
                            </div>
                            <b-button 
                                variant="primary"
                                @click="addStatus"
                            >
                                <strong>+</strong>
                            </b-button>
                        </td>
                    </tr>
                    <tr v-for="status in curationCopy.curation_statuses" :key="status.pivot.id">
                        <td>
                            <label :for="'status-date-'+status.id"><strong>{{status.name}}</strong></label>
                        </td>
                        <td class="d-flex align-items-center">
                            <div class="flex-grow-1 me-2">
                                <date-input
                                    :id="'status-date-'+status.id"
                                    :model-value="status.pivot.status_date"
                                    @update:model-value="updateStatusDate(status.pivot,$event)"
                                ></date-input>
                            </div>
                            <b-button @click="removeStatusEntry(status)"><strong>x</strong></b-button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </b-modal>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useStore } from 'vuex'
import DateInput from '../../forms/DateInput.vue'
import moment from 'moment'
import { formatDate } from '../../../filters'
import CurationStatusHistory from '../StatusHistory.vue'

const props = defineProps({
    modelValue: {
        required: true,
        type: Object
    }
})

const store = useStore()
const curationCopy = ref({})
const modalVisible = ref(false)
const newStatusDate = ref(new Date())
const newStatusId = ref(null)
const errors = ref([])

const user = computed(() => store.getters.getUser)
const curationStatuses = computed(() => store.getters['curationStatuses/Items'])
const statusOptions = computed(() => {
    return curationStatuses.value.filter(status => user.value.canSelectCurationStatus(status, curationCopy.value))
})

watch(() => props.modelValue, value => {
    curationCopy.value = JSON.parse(JSON.stringify(value))
}, { immediate: true, deep: true })

function addStatus() {
    store.dispatch('curations/linkNewStatus', {
        curation: curationCopy.value,
        data: {
            curation_status_id: newStatusId.value,
            status_date: formatDate(newStatusDate.value, 'YYYY-MM-DD')
        }
    }).then(() => {
        newStatusId.value = null
        newStatusDate.value = new Date()
    }).catch(response => {
        errors.value = response.data.errors
    })
}

function updateStatusDate(pivot, newDate) {
    if (!pivot || moment(pivot.status_date).diff(newDate) == 0) {
        return
    }
    store.dispatch('curations/updateStatusDate', {
        curation: curationCopy.value,
        pivotId: pivot.id,
        statusDate: moment(newDate).format('YYYY-MM-DD')
    }).catch(response => {
        errors.value = response.data.errors
    })
}

function removeStatusEntry(status) {
    store.dispatch('curations/unlinkStatus', {
        curation: curationCopy.value,
        pivotId: status.pivot.id
    })
}

function submitAll() {
    if (newStatusId.value != null) {
        addStatus()
    }
}
</script>
