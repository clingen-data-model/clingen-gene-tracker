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
            <DataTable :value="documents" stacked="sm">
                <Column field="id" header="ID" sortable></Column>
                <Column field="name" header="Name" sortable></Column>
                <Column field="category.name" header="Category" sortable></Column>
                <Column header="Created" sortable :field="(value) => $filters.formatDate(value, 'YYYY-MM-DD')" />
                <Column field="uploader.name" header="Uploaded by" sortable></Column>
                <Column header="Action" field="action" sortable>
                    <template #body="{ data: document }">
                        <a href="#" @click.prevent="downloadFile(document)" title="Download document">
                            <i class="material-icons">cloud_download</i>
                        </a>
                        <a href="#" @click.prevent="showDetails(document)" title="Detailed information">
                            <i class="material-icons">info</i>
                        </a>
                        <a href="#" title="Delete document" class="text-danger"
                            @click.prevent="deleteDocument(document)" v-if="user.canEditCuration(curation)">
                            <i class="material-icons">delete</i>
                        </a>
                    </template>
                </Column>

                <template v-slot:cell(action)="{ item: document }">
                </template>
            </DataTable>
        </div>
        <Dialog modal v-model:visible="showDetailedInfo" hide-footer v-if="currentDocument"
            :header="currentDocument.name" size="lg">
            <dl>
                <div class="info-row">
                    <dt>Name:</dt>
                    <dd>{{ currentDocument.name }}</dd>
                </div>
                <div class="info-row">
                    <dt>File name:</dt>
                    <dd>{{ currentDocument.file_name ? currentDocument.file_name : '--' }}</dd>
                </div>
                <div class="info-row">
                    <dt>Category:</dt>
                    <dd>{{ currentDocument.category ? currentDocument.category.name : '--' }}</dd>
                </div>
                <div class="info-row">
                    <dt>Date uploaded:</dt>
                    <dd>{{ $filters.formatDate(currentDocument.created_at, 'YYYY-MM-DD') }}</dd>
                </div>
                <div class="info-row">
                    <dt>Uploaded by:</dt>
                    <dd>{{ (currentDocument.uploader) ? currentDocument.uploader.name : '--' }}</dd>
                </div>
                <div class="info-row">
                    <dt>Notes:</dt>
                    <dd>{{ currentDocument.notes ? currentDocument.notes : '--' }}</dd>
                </div>
            </dl>
            <div class="mt-2">
                <button class="btn btn-primary text-middle btn-sm" @click="downloadFile(currentDocument)"
                    title="Download document">
                    Download document
                </button>
            </div>
        </Dialog>
    </div>
</template>

<script>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import DocumentUploader from './DocumentUploader.vue'
import getAllUploads from '../../../resources/uploads/get_all_uploads'
import { mapGetters } from 'vuex';

export default {
    components: {
        Column,
        DataTable,
        Dialog,
        DocumentUploader,
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
        }
    },
    computed: {
        ...mapGetters({ user: 'getUser' })
    },
    watch: {
        curation() {

            this.getDocuments()
        }
    },
    methods: {
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
            axios.get('/api/curations/' + this.curation.id + '/uploads/' + document.id + '/file',
                {
                    responseType: 'blob'
                })
                .then(response => {
                    console.log(response.data);
                    const data = response.data;
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
            if (confirm('Are you sure you want to delete the document ' + document.name + '?')) {
                this.documents.splice(this.documents.findIndex(doc => doc.id = document.id), 1);
                axios.delete('/api/curations/' + this.curation.id + '/uploads/' + document.id)
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

<style scoped>
div.info-row {
    @apply grid grid-cols-3;
}

div.info-row dt {
    @apply col-span-1;
    font-weight: 600;
}

div.info-row dd {
    @apply col-span-2;
}
</style>