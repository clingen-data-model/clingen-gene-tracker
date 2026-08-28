

<template>
    <div>
        <h4>
            <document-uploader 
                :curation="curation" 
                class="float-end"
                v-on:uploaded="$refs.docList.getDocuments()"
                v-if="user.canEditCuration(curation) && !curation.is_archived"
            ></document-uploader>
            Documents
        </h4>
        
        <documents-list :curation="curation" ref="docList"></documents-list>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import DocumentUploader from './DocumentUploader.vue'
import DocumentsList from './DocumentsList.vue'

defineProps({
    curation: {
        reqired: true,
        type: Object
    }
})

const store = useStore()
const user = computed(() => store.getters.getUser)
</script>
