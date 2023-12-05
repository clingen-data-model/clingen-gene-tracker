

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
            <div class="form-row">
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
                        maxlegnth="65535"
                        :disabled="uploading"
                    ></textarea>
                    <validation-error :messages="errors.notes"></validation-error>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script>
    import ValidationError from '../../ValidationError.vue'
    import {mapGetters} from 'vuex'

    export default {
        components: {
            ValidationError
        },
        props: {
            curation: {
                required: true,
                type: Object
            }
        },  
        data() {
            return {
                showModal: false,
                categories: [],
                newUpload: {},
                errors: {},
                uploading: false
            }
        },
        computed: {
            ...mapGetters({
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
            uploadFile(evt) {
                this.initErrors();
                evt.preventDefault();

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
                    this.$nextTick(() => this.$refs.uploadModal.hide());
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