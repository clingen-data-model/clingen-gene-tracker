<template>
    <div class="mb-2">
        <button class="btn btn-primary btn-sm" @click="showModal = true">Add Document</button>

        <Dialog modal v-model:visible="showModal" header="Upload a Document" @ok="uploadFile" @cancel="clearForm"
            ref="uploadModal" :ok-disabled="uploading" :cancel-disabled="uploading"
            :ok-title="uploading ? 'Uploading...' : 'Upload'">
            <div class="form-row">
                <label class="col-sm-2" for="file-field">
                    File:
                </label>
                <div class="col-sm-10">
                    <div class="d-flex justify-content-between">
                        <div><input type="file" ref="uploadField" class="form-control-file" id="file-field"
                                @change="onFilesSelected" :disabled="uploading"></div>
                        <div>
                            <small class="text-secondary material-icons cursor-pointer"
                                @click="info_collapsed = !info_collapsed">info</small>
                        </div>
                    </div>
                    <Panel header="" id="file-info-collapse" :collapsed="info_collapsed">
                        <div><small class="text-muted">Supported types: {{ (supportedMimes || []).join(', ') }}</small>
                        </div>
                        <div><small class="text-muted">Max size: {{ maxUploadSize }}</small></div>
                    </Panel>
                    <validation-error :messages="errors.file"></validation-error>
                </div>
            </div>
            <div class="form-row">
                <label class="col-sm-2" for="name">Name:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control form-control-sm" id="name" v-model="newUpload.name"
                        maxlength="255" :disabled="uploading">
                    <validation-error :messages="errors.name"></validation-error>
                </div>
            </div>
            <div class="form-row" v-if="categories.length > 0">
                <label for="category_id" class="col-sm-2">Category:</label>
                <div class="col-sm-10">
                    <select name="category_id" id="category_id" class="form-control form-control-sm"
                        v-model="newUpload.upload_category_id" :disabled="uploading">
                        <option value="">None</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <validation-error :messages="errors.upload_category_id"></validation-error>
                </div>
            </div>
            <div class="form-row">
                <label for="notes" class="col-sm-2">
                    Notes:
                </label>
                <div class="col-sm-10">
                    <textarea name="notes" v-model="newUpload.notes" id="notes" cols="30" rows="5"
                        class="form-control form-control-sm" maxlegnth="65535" :disabled="uploading"></textarea>
                    <validation-error :messages="errors.notes"></validation-error>
                </div>
            </div>
            <div class="button-group flex justify-end gap-2">
                <button class="btn btn-primary" @click="uploadFile" :disabled="uploading">Upload</button>
                <button class="btn btn-secondary" @click="clearForm" :disabled="uploading">Cancel</button>
            </div>
            <div class="alert alert-info mt-2" v-if="uploading">
                <small>Uploading...</small>
            </div>

        </Dialog>
    </div>
</template>

<script setup>
import Panel from 'primevue/panel'
import Dialog from 'primevue/dialog'
import ValidationError from '../../ValidationError.vue'
import { computed, onMounted } from 'vue'

const props = defineProps({
    curation: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['uploaded'])

const showModal = ref(false)
const categories = ref([])
const newUpload = ref({})
const errors = ref({})
const uploading = ref(false)
const info_collapsed = ref(true)
const selectedFiles = ref([])

const maxUploadSize = computed(() => {
    return useStore().getters.getMaxUploadSize
})
const supportedMimes = computed(() => {
    return useStore().getters.getSupportedMimes
})

const getUploadCategories = () => {
    window.axios.get('/api/upload-categories')
        .then(response => categories.value = response.data.data)
}
const initNewUpload = () => {
    newUpload.value = {
        name: '',
        upload_category_id: '',
        notes: ''
    }
}
const initErrors = () => {
    errors.value = {}
}
const clearForm = () => {
    initNewUpload();
    initErrors();
}
const onFilesSelected = (event) => {
    // FIXME: upload only processes the first file... Should give an error if multiple files are selected
    selectedFiles.value = event.target.files;
    if (newUpload.value.name == '') {
        newUpload.value.name = selectedFiles.value[0].name;
    }
}
const uploadFile = (evt) => {
    console.log('uploading file');
    initErrors();
    evt.preventDefault();

    let formData = new FormData();
    formData.append('curation_id', props.curation.id);
    formData.append('name', newUpload.value.name);
    formData.append('file', selectedFiles.value[0]);
    formData.append('upload_category_id', newUpload.value.upload_category_id);
    formData.append('notes', newUpload.value.notes);

    uploading.value = true;

    return window.axios.post(
        `/api/curations/${props.curation.id}/uploads`,
        formData,
        {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }
    )
        .then(response => {
            emit('uploaded');
            clearForm();
            showModal.value = false;
        })
        .catch(error => {
            if (error.response.status == 422) {
                errors = error.response.data.errors
                return
            }
            if (error.response.status == 413) {
                errors.value = { file: ['The file was too large'] }
                return;
            }
            alert('There was an unkown problem with your file upload.');
            uploading.value = false;
        })
        .then(() => { uploading.value = false })
}

onMounted(() => {
    getUploadCategories();
    initNewUpload();
    initErrors();
})
</script>

<style scoped>
:deep(.p-panel) {
    border: 0 none;
}
</style>