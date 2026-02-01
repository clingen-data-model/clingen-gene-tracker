

<template>
    <div>
        <h4>
            <document-uploader
                :curation="curation"
                class="float-end"
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
    import { mapState } from 'pinia';
    import { useAppStore } from '../../../stores/app';

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
            ...mapState(useAppStore, {user: 'getUser'})
        },
        methods: {
        }
    }
</script>
