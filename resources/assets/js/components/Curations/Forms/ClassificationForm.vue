<style></style>

<template>
    <div>
        <b-button 
            variant="info"
            size="sm" 
            class="form-control mb-2"
            @click="modalVisible = true"
        >Add or update classification</b-button>

        <classification-history :curation="modelValue"></classification-history>

        <b-modal 
            v-model="modalVisible"
            @hide="submitAll"
            size="lg"
        >
            <template #modal-header>
                <div>
                    <h3>Update Classification</h3>
                </div>
            </template>
            <table class="table">
                <thead>
                    <tr>
                        <th>Classification</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <b-form-select v-model="newClassificationId">
                                <option :value="null">Select...</option>
                                <option v-for="classification in classificationOptions"
                                    :key="classification.id"
                                    :value="classification.id"
                                >
                                    {{classification.name}}
                                </option>
                            </b-form-select>
                            <div class="text-danger" v-if="errors.classification_id">
                                <div v-for="message in errors.classification_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="d-flex align-items-center">
                            <date-input 
                                v-model="newClassificationDate"
                                class="me-2"
                            ></date-input>
                            <b-button 
                                variant="primary"
                                @click="addClassification"
                            >
                                <strong>+</strong>
                            </b-button>
                        </td>
                    </tr>
                    <tr v-for="classification in curationCopy.classifications" :key="classification.pivot.id">
                        <td>
                            <label :for="'classification-date-'+classification.id"><strong>{{classification.name}}</strong></label>
                        </td>
                        <td class="d-flex align-items-center">
                            <date-input
                                :id="'classification-date-'+classification.id"
                                :model-value="classification.pivot.classification_date"
                                class="me-2"
                                @update:model-value="updateclassificationDate(classification.pivot,$event)"
                            ></date-input>
                            <b-button @click="removeclassificationEntry(classification)"><strong>x</strong></b-button>
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
import ClassificationHistory from '../ClassificationHistory.vue'
import DateInput from '../../forms/DateInput.vue'
import moment from 'moment'
import { formatDate } from '../../../filters'

const props = defineProps({
    modelValue: {
        required: true,
        type: Object
    }
})

const store = useStore()
const curationCopy = ref({})
const modalVisible = ref(false)
const newClassificationDate = ref(new Date())
const newClassificationId = ref(null)
const errors = ref({})

const classifications = computed(() => store.getters['classifications/Items'])
const classificationOptions = computed(() => classifications.value)

watch(() => props.modelValue, value => {
    curationCopy.value = JSON.parse(JSON.stringify(value))
}, { immediate: true, deep: true })

function addClassification() {
    store.dispatch('curations/linkNewClassification', {
        curation: curationCopy.value,
        data: {
            classification_id: newClassificationId.value,
            classification_date: formatDate(newClassificationDate.value, 'YYYY-MM-DD')
        }
    }).then(() => {
        newClassificationId.value = null
        newClassificationDate.value = new Date()
    }).catch(error => {
        errors.value = error.response.data.errors
    })
}

function updateclassificationDate(pivot, newDate) {
    if (!pivot || moment(pivot.classification_date).diff(newDate) == 0) {
        return
    }
    store.dispatch('curations/updateClassification', {
        curation: curationCopy.value,
        pivotId: pivot.id,
        data: {
            classification_id: pivot.classification_id,
            classification_date: moment(newDate).format('YYYY-MM-DD')
        }
    }).catch(response => {
        errors.value = response.data.errors
    })
}

function removeclassificationEntry(classification) {
    store.dispatch('curations/unlinkClassification', {
        curation: curationCopy.value,
        pivotId: classification.pivot.id
    })
}

function submitAll() {
    if (newClassificationId.value != null) {
        addClassification()
    }
}
</script>
