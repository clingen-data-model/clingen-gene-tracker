

<template>
    <div>
        <div class="alert alert-info" v-if="loadingDocuments">Loading...</div>
        <div class="alert alert-light border" v-if="!loadingDocuments && documents.length == 0">
            No documents found
        </div>
        <div v-if="documents.length > 0">
            <div class="form-inline mb-1">
                <label for="list-filter-input">Filter:</label>&nbsp;
                <input type="text" class="form-control form-control-sm" v-model="filter">
            </div>
            <DataTable :value="filteredDocuments" :small="true">
                <Column field="id" header="Id" :sortable="true"></Column>
                <Column field="name" header="Name" :sortable="true"></Column>
                <Column field="category.name" header="Category" :sortable="true">
                    <template #body="{data}">{{ data.category ? data.category.name : '' }}</template>
                </Column>
                <Column field="created_at" header="Created" :sortable="true">
                    <template #body="{data}">{{ formatDate(data.created_at, 'YYYY-MM-DD') }}</template>
                </Column>
                <Column field="uploader.name" header="Uploaded by" :sortable="true">
                    <template #body="{data}">{{ data.uploader ? data.uploader.name : '' }}</template>
                </Column>
                <Column header="Actions">
                    <template #body="{data: document}">
                        <a href="#" @click.prevent="downloadFile(document)" title="Download document">
                            <i class="material-icons">cloud_download</i>
                        </a>
                        <a href="#" @click.prevent="showDetails(document)" title="Detailed information">
                            <i class="material-icons">info</i>
                        </a>
                        <a href="#"
                            title="Delete document"
                            class="text-danger"
                            @click.prevent="deleteDocument(document)"
                            v-if="user.canEditCuration(curation)"
                        >
                            <i class="material-icons">delete</i>
                        </a>
                    </template>
                </Column>
            </DataTable>
        </div>
        <Dialog
            :visible.sync="showDetailedInfo"
            :header="currentDocument ? currentDocument.name : ''"
            :modal="true"
            :style="{width: '50vw'}"
            v-if="currentDocument"
        >
            <dl class="row">
                <dt class="col-md-2">Name:</dt>
                <dd class="col-md-10">{{currentDocument.name}}</dd>

                <dt class="col-md-2">File name:</dt>
                <dd class="col-md-10">{{currentDocument.file_name ? currentDocument.file_name : '--'}}</dd>

                <dt class="col-md-2">Category:</dt>
                <dd class="col-md-10">{{currentDocument.category ? currentDocument.category.name : '--'}}</dd>

                <dt class="col-md-2">Date uploaded:</dt>
                <dd class="col-md-10">{{formatDate(currentDocument.created_at, 'YYYY-MM-DD')}}</dd>

                <dt class="col-md-2">Uploaded by:</dt>
                <dd class="col-md-10">{{(currentDocument.uploader) ? currentDocument.uploader.name : '--'}}</dd>

                <dt class="col-md-2">Notes:</dt>
                <dd class="col-md-10">{{currentDocument.notes ? currentDocument.notes : '--'}}</dd>
            </dl>
            <div class="mt-2">
                <button
                    class="btn btn-primary text-middle btn-sm"
                    @click="downloadFile(currentDocument)"
                    title="Download document"
                >
                    Download document
                </button>
            </div>
        </Dialog>
    </div>
</template>

<script>
    import DocumentUploader from './DocumentUploader.vue'
    import getAllUploads from '../../../resources/uploads/get_all_uploads'
    import { mapGetters } from 'vuex';
    import formatDate from '../../../helpers/formatDate'

    export default {
        components: {
            DocumentUploader
        },
        props: {
            curation: {
                reqired: true,
                type: Object
            }
        },
        data() {
            return {
                showDetailedInfo: false,
                loadingDocuments: false,
                documents: [],
                currentDocument: null,
                filter: '',
            }
        },
        computed: {
            ...mapGetters({user: 'getUser'}),
            filteredDocuments() {
                if (!this.filter) return this.documents;
                const f = this.filter.toLowerCase();
                return this.documents.filter(doc =>
                    (doc.name && doc.name.toLowerCase().includes(f)) ||
                    (doc.id && String(doc.id).includes(f)) ||
                    (doc.category && doc.category.name && doc.category.name.toLowerCase().includes(f)) ||
                    (doc.uploader && doc.uploader.name && doc.uploader.name.toLowerCase().includes(f))
                );
            }
        },
        watch: {
            curation() {
                this.getDocuments()
            }
        },
        methods: {
            formatDate,
            async getDocuments() {
                if (!this.curation.id) {
                    return;
                }

                this.loadingDocuments = true;
                this.documents = await getAllUploads(
                    {
                        with: ['category', 'uploader'],
                        where: {
                            curation_id: this.curation.id
                        }
                    }
                )
                this.loadingDocuments = false;
            },
            showDetails(document) {
                this.currentDocument = document
                this.showDetailedInfo = true;
            },
            downloadFile(document) {
                axios.get('/api/curations/'+this.curation.id+'/uploads/'+document.id+'/file',
                    {
                        responseType: 'blob'
                    })
                    .then(response => {
                        console.log(response.data);
                        const data  = response.data;
                        let a = window.document.createElement('a');
                        let url = window.URL.createObjectURL(data);
                        a.href = url;
                        a.download = document.name;
                        window.document.body.append(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch(error => {
                        if (error.response && error.response.status == 404) {
                            alert('We couldn\'t seem to find the file you requested.');
                            return;
                        }
                        throw error;
                    })
            },
            deleteDocument(document) {
                if (confirm('Are you sure you want to delete the document '+document.name+'?')) {
                    this.documents.splice(this.documents.findIndex(doc => doc.id = document.id), 1);
                    axios.delete('/api/curations/'+this.curation.id+'/uploads/'+document.id)
                        .then(response => {
                        })
                        .catch(error => {
                            this.getDocuments();
                            alert('There was a problem deleting the document.  Contact the administrator if the problem persists.');
                        })
                }
            }
        },
        mounted() {
            this.getDocuments();
        }
    }
</script>
