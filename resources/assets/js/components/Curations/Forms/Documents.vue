<template>
    <div>
        <documents-card :curation="curation"></documents-card>
    </div>
</template>
<script>
import curationFormMixin from '../../../mixins/curation_form_mixin'
import DocumentsCard from '../Documents/DocumentsCard.vue'

export default {
    components: {
        DocumentsCard,
    },
    mixins: [
        curationFormMixin,
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
