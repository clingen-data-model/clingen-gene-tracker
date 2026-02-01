

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
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th>Uploaded by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="document in filteredDocuments" :key="document.id">
                        <td>{{ document.id }}</td>
                        <td>{{ document.name }}</td>
                        <td>{{ document.category ? document.category.name : '' }}</td>
                        <td>{{ $formatDate(document.created_at, 'YYYY-MM-DD') }}</td>
                        <td>{{ document.uploader ? document.uploader.name : '' }}</td>
                        <td>
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
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Dialog v-model:visible="showDetailedInfo" modal v-if="currentDocument" :header="currentDocument.name" :style="{ width: '50vw' }">
            <dl class="row">
                    <dt class="col-md-2">Name:</dt>
                    <dd class="col-md-10">{{currentDocument.name}}</dd>

                    <dt class="col-md-2">File name:</dt>
                    <dd class="col-md-10">{{currentDocument.file_name ? currentDocument.file_name : '--'}}</dd>

                    <dt class="col-md-2">Category:</dt>
                    <dd class="col-md-10">{{currentDocument.category ? currentDocument.category.name : '--'}}</dd>

                    <dt class="col-md-2">Date uploaded:</dt>
                    <dd class="col-md-10">{{ $formatDate(currentDocument.created_at, 'YYYY-MM-DD') }}</dd>

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
    import Dialog from 'primevue/dialog'
    import getAllUploads from '../../../resources/uploads/get_all_uploads'
    import { mapState } from 'pinia';
    import { useAppStore } from '../../../stores/app';

    export default {
        components: {
            Dialog
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
            ...mapState(useAppStore, {user: 'getUser'}),
            filteredDocuments() {
                if (!this.filter) {
                    return this.documents;
                }
                const filterLower = this.filter.toLowerCase();
                return this.documents.filter(doc => {
                    return (doc.name && doc.name.toLowerCase().includes(filterLower))
                        || (doc.id && String(doc.id).includes(filterLower))
                        || (doc.category && doc.category.name && doc.category.name.toLowerCase().includes(filterLower))
                        || (doc.uploader && doc.uploader.name && doc.uploader.name.toLowerCase().includes(filterLower));
                });
            }
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
                axios.get('/api/curations/'+this.curation.id+'/uploads/'+document.id+'/file',
                    {
                        responseType: 'blob'
                    })
                    .then(response => {
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
                    this.documents.splice(this.documents.findIndex(doc => doc.id == document.id), 1);
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
