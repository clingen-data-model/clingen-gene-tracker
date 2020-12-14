<template>
    <div>
        <documents-card :curation="curation"></documents-card>
    </div>
</template>
<script>
import curationFormMixin from '../../../mixins/curation_form_mixin'
import DocumentsCard from '../Documents/DocumentsCard'

export default {
    components: {
        DocumentsCard,
    },
    mixins: [
        curationFormMixin, // handles syncing of prop value to updatedCuration
    ],
    data() {
        return {
            page: 'documents',            
            curation: {}
        }
    },
    watch: {
        updatedCuration() {
            this.curation = (this.updatedCuration) ?  this.updatedCuration : {}
        }
    }
}
</script>