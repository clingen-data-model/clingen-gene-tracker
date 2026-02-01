

<template>
    <div class="mb-2">
        <button
            class="btn btn-primary btn-sm"
            @click="showModal = true"
        >Add Document</button>

        <Dialog
            v-model:visible="showModal"
            header="Upload a Document"
            modal
            :style="{ width: '50vw' }"
        >
            <div class="form-row">
                <label class="col-sm-2" for="file-field">
                    File:
                </label>
                <div class="col-sm-10">
                    <div class="d-flex justify-content-between">
                        <div><input type="file" ref="uploadField" class="form-control-file" id="file-field" @change="prepopulateName()" :disabled="uploading"></div>
                        <div>
                            <small class="text-secondary cursor-pointer" @click="showFileInfo = !showFileInfo">info</small>
                        </div>
                    </div>
                    <div v-show="showFileInfo">
                        <div><small class="text-muted">Supported types: {{supportedMimes.join(', ')}}</small></div>
                        <div><small class="text-muted">Max size: {{maxUploadSize}}</small></div>
                    </div>
                    <validation-error :messages="errors.file"></validation-error>
                </div>
            </div>
            <div class="form-row">
                <label class="col-sm-2" for="name">Name:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control form-control-sm" id="name" v-model="newUpload.name" maxlength="255" :disabled="uploading">
                    <validation-error :messages="errors.name"></validation-error>
                </div>
            </div>
            <div class="form-row" v-if="categories.length > 0">
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
            <div class="form-row">
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
                        maxlength="65535"
                        :disabled="uploading"
                    ></textarea>
                    <validation-error :messages="errors.notes"></validation-error>
                </div>
            </div>
            <template #footer>
                <button type="button" class="btn btn-secondary" @click="clearForm(); showModal = false" :disabled="uploading">Cancel</button>
                <button type="button" class="btn btn-primary" @click="uploadFile" :disabled="uploading">
                    {{ uploading ? 'Uploading...' : 'Upload' }}
                </button>
            </template>
        </Dialog>
    </div>
</template>

<script>
    import ValidationError from '../../ValidationError.vue'
    import Dialog from 'primevue/dialog'
    import { mapState } from 'pinia'
    import { useAppStore } from '../../../stores/app'

    export default {
        components: {
            ValidationError,
            Dialog
        },
        props: {
            curation: {
                required: true,
                type: Object
            }
        },
        emits: ['uploaded'],
        data() {
            return {
                showModal: false,
                showFileInfo: false,
                categories: [],
                newUpload: {},
                errors: {},
                uploading: false
            }
        },
        computed: {
            ...mapState(useAppStore, {
                maxUploadSize: 'getMaxUploadSize',
                supportedMimes: 'getSupportedMimes'
            })
        },
        methods: {
            getUploadCategories() {
                window.axios.get('/api/upload-categories')
                    .then(response => this.categories = response.data.data)
            },
            initNewUpload() {
                this.newUpload = {
                    name: '',
                    upload_category_id: '',
                    notes: ''
                }
            },
            initErrors() {
                this.errors = {}
            },
            clearForm() {
                this.initNewUpload();
                this.initErrors();
            },
            prepopulateName() {
                if (this.newUpload.name == '') {
                    this.newUpload.name = this.$refs.uploadField.files[0].name;
                }
            },
            uploadFile() {
                this.initErrors();

                let formData = new FormData();
                formData.append('curation_id', this.curation.id);
                formData.append('name', this.newUpload.name);
                formData.append('file', this.$refs.uploadField.files[0]);
                formData.append('upload_category_id', this.newUpload.upload_category_id);
                formData.append('notes', this.newUpload.notes);

                this.uploading = true;

                return window.axios.post(
                    `/api/curations/${this.curation.id}/uploads`,
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }
                )
                .then(response => {
                    this.$emit('uploaded');
                    this.clearForm();
                    this.showModal = false;
                })
                .catch(error => {
                    if (error.response.status == 422) {
                        this.errors = error.response.data.errors
                        return
                    }
                    if (error.response.status == 413) {
                        this.errors = {file: ['The file was too large']}
                        return;
                    }
                    alert('There was an unkown problem with your file upload.');
                })
                .then(() => {this.uploading = false})

            },
            launchFileSelector() {
                this.$refs.uploadField.click();
            }
        },
        mounted() {
            this.getUploadCategories();
            this.initNewUpload();
            this.initErrors();
        }

}
</script>
