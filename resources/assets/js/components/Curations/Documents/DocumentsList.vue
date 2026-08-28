

<template>
    <div>
        <div class="alert alert-info" v-if="loadingDocuments">Loading...</div>
        <div class="alert alert-light border" v-if="!loadingDocuments && documents.length == 0">
            No documents found
        </div>
        <div v-if="documents.length > 0">
            <div class="d-flex align-items-center mb-1">
                <label for="list-filter-input">Filter:</label>&nbsp;
                <input type="text" class="form-control form-control-sm" v-model="filter">
            </div>
            <b-table 
                :fields="fields" 
                :items="documents" 
                :filter="filter" 
                :filter-included-fields="filteredFields"
                stacked="sm"
            >
                <template v-slot:cell(action)="{item: document}">
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
                        v-if="user.canEditCuration(curation) && !curation.is_archived"
                    >
                        <i class="material-icons">delete</i>
                </a>
                </template>    
            </b-table>
        </div>
        <b-modal v-model="showDetailedInfo" hide-footer v-if="currentDocument" :title="currentDocument.name" size="lg">
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
        </b-modal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useStore } from 'vuex'
import getAllUploads from '../../../resources/uploads/get_all_uploads'
import { formatDate } from '../../../filters'

const props = defineProps({
    curation: {
        reqired: true,
        type: Object
    }
})

const store = useStore()
const user = computed(() => store.getters.getUser)
const showDetailedInfo = ref(false)
const loadingDocuments = ref(false)
const documents = ref([])
const currentDocument = ref(null)
const filter = ref('')
const fields = [
    {
        key: 'id',
        sortable: true
    },
    {
        key: 'name',
        sortable: true
    },
    {
        key: 'category.name',
        sortable: true,
        label: 'Category'
    },
    {
        key: 'created_at',
        label: 'Created',
        sortable: true,
        formatter: (value, key, item) => {
            return formatDate(value, 'YYYY-MM-DD')
        }
    },
    {
        key: 'uploader.name',
        label: 'Uploaded by',
        sortable: true
    },
    'action'
]
const filteredFields = ['name', 'id', 'category', 'uploader', 'uploader']

async function getDocuments() {
    if (!props.curation.id) {
        return
    }

    loadingDocuments.value = true
    documents.value = await getAllUploads({
        with: ['category', 'uploader'],
        where: {
            curation_id: props.curation.id
        }
    })
    loadingDocuments.value = false
}

function showDetails(document) {
    currentDocument.value = document
    showDetailedInfo.value = true
}

function downloadFile(document) {
    axios.get('/api/curations/' + props.curation.id + '/uploads/' + document.id + '/file', {
        responseType: 'blob'
    })
        .then(response => {
            const data = response.data
            const a = window.document.createElement('a')
            const url = window.URL.createObjectURL(data)
            a.href = url
            a.download = document.name
            window.document.body.append(a)
            a.click()
            a.remove()
            window.URL.revokeObjectURL(url)
        })
        .catch(error => {
            if (error.response && error.response.status == 404) {
                alert('We couldn\'t seem to find the file you requested.')
                return
            }

            throw error
        })
}

function deleteDocument(document) {
    if (confirm('Are you sure you want to delete the document ' + document.name + '?')) {
        documents.value.splice(documents.value.findIndex(doc => doc.id === document.id), 1)
        axios.delete('/api/curations/' + props.curation.id + '/uploads/' + document.id)
            .catch(() => {
                getDocuments()
                alert('There was a problem deleting the document.  Contact the administrator if the problem persists.')
            })
    }
}

watch(() => props.curation, () => {
    getDocuments()
})

onMounted(() => {
    getDocuments()
})

defineExpose({ getDocuments })
</script>
