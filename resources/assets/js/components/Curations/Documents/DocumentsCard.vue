

<template>
    <div>
        <h4>
            <document-uploader 
                :curation="curation" 
                class="float-right"
                v-on:uploaded="$refs.docList.getDocuments()"
                v-if="user.canEditCuration(curation)"
            ></document-uploader>
            Documents
        </h4>
        
        <documents-list :curation="curation" ref="docList"></documents-list>
    </div>
</template>

<script>
    import DocumentUploader from './DocumentUploader.vue';
    import DocumentsList from './DocumentsList.vue';
    import { mapGetters } from 'vuex';

    export default {
        components: {
            DocumentUploader,
            DocumentsList
        },
        props: {
            curation: {
                reqired: true,
                type: Object
            }
        },
        data() {
            return {
            }
        },
        computed: {
            ...mapGetters({user: 'getUser'})
        },
        methods: {
        }
    }
</script>