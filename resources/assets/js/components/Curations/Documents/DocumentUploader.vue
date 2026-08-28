

<template>
    <div class="mb-2">
        <button 
            class="btn btn-primary btn-sm" 
            @click="showModal = true"
            
        >Add Document</button>

        <b-modal 
            v-model="showModal"
            title="Upload a Document"
            @ok="uploadFile"
            @cancel="clearForm"
            ref="uploadModal"
            :ok-disabled="uploading"
            :cancel-disabled="uploading"
            :ok-title="uploading ? 'Uploading...' : 'Upload'"
        >
            <div class="row">
                <label class="col-sm-2" for="file-field">
                    File:
                </label>
                <div class="col-sm-10">
                    <div class="d-flex justify-content-between">
                        <div><input type="file" ref="uploadField" class="form-control-file" id="file-field" @change="prepopulateName()" :disabled="uploading"></div>
                        <div>
                            <small class="text-secondary material-icons cursor-pointer" v-b-toggle.file-info-collapse>info</small>
                        </div>
                    </div>
                    <b-collapse id="file-info-collapse">
                        <div><small class="text-muted">Supported types: {{supportedMimes.join(', ')}}</small></div>
                        <div><small class="text-muted">Max size: {{maxUploadSize}}</small></div>
                    </b-collapse>
                    <validation-error :messages="errors.file"></validation-error>
                </div>
            </div>
            <div class="row">
                <label class="col-sm-2" for="name">Name:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control form-control-sm" id="name" v-model="newUpload.name" maxlength="255" :disabled="uploading">
                    <validation-error :messages="errors.name"></validation-error>
                </div>
            </div>
            <div class="row" v-if="categories.length > 0">
                <label for="category_id" class="col-sm-2">Category:</label>
                <div class="col-sm-10">
                    <select name="category_id" id="category_id" class="form-control form-control-sm" v-model="newUpload.upload_category_id" :disabled="uploading">
                        <option value="">None</option>
                        <option 
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{category.name}}
                        </option>
                    </select>
                    <validation-error :messages="errors.upload_category_id"></validation-error>
                </div>
            </div>
            <div class="row">
                <label for="notes" class="col-sm-2">
                    Notes:
                </label>
                <div class="col-sm-10">
                    <textarea 
                        name="notes" 
                        v-model="newUpload.notes" 
                        id="notes" 
                        cols="30" 
                        rows="5" 
                        class="form-control form-control-sm" 
                        maxlegnth="65535"
                        :disabled="uploading"
                    ></textarea>
                    <validation-error :messages="errors.notes"></validation-error>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import ValidationError from '../../ValidationError.vue'

const props = defineProps({
    curation: {
        required: true,
        type: Object
    }
})
const emit = defineEmits(['uploaded'])

const store = useStore()
const showModal = ref(false)
const categories = ref([])
const newUpload = ref({})
const errors = ref({})
const uploading = ref(false)
const uploadField = ref(null)
const uploadModal = ref(null)

const maxUploadSize = computed(() => store.getters.getMaxUploadSize)
const supportedMimes = computed(() => store.getters.getSupportedMimes)

function getUploadCategories() {
    window.axios.get('/api/upload-categories')
        .then(response => {
            categories.value = response.data.data
        })
}

function initNewUpload() {
    newUpload.value = {
        name: '',
        upload_category_id: '',
        notes: ''
    }
}

function initErrors() {
    errors.value = {}
}

function clearForm() {
    initNewUpload()
    initErrors()
}

function prepopulateName() {
    if (newUpload.value.name == '') {
        newUpload.value.name = uploadField.value.files[0].name
    }
}

function uploadFile(evt) {
    initErrors()
    evt.preventDefault()

    const formData = new FormData()
    formData.append('curation_id', props.curation.id)
    formData.append('name', newUpload.value.name)
    formData.append('file', uploadField.value.files[0])
    formData.append('upload_category_id', newUpload.value.upload_category_id)
    formData.append('notes', newUpload.value.notes)

    uploading.value = true

    return window.axios.post(
        `/api/curations/${props.curation.id}/uploads`,
        formData,
        {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }
    )
        .then(() => {
            emit('uploaded')
            clearForm()
            nextTick(() => uploadModal.value.hide())
        })
        .catch(error => {
            if (error.response.status == 422) {
                errors.value = error.response.data.errors
                return
            }
            if (error.response.status == 413) {
                errors.value = {file: ['The file was too large']}
                return
            }
            alert('There was an unkown problem with your file upload.')
        })
        .then(() => {
            uploading.value = false
        })
}

onMounted(() => {
    getUploadCategories()
    initNewUpload()
    initErrors()
})
</script>
