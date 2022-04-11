<style></style>

<template>
    <div class="component-container w-50">
        <classification-history :curation="value"></classification-history>
        <div class="alert alert-secondary">
            Classifications must be added to a curation via the GCI.
        </div>

    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import CurationNotifications from './ExistingCurationNotification.vue'
    import DateField from '../../DateField.vue'
    import ValidationError from '../../ValidationError.vue'
    import ClassificationHistory from '../ClassificationHistory.vue'

    export default {
        components: {
            CurationNotifications,
            DateField,
            ValidationError,
            ClassificationHistory
        },
        mixins: [
            curationFormMixin,
        ],
        data() {
            return {
                page: 'mondo',
                updatedCuration: {}
            }
        },
        computed: {
            ...mapGetters('classifications', {
               classifications: 'Items',
            }),
        },
        methods: {
            ...mapActions('classifications', {
                getAllClassifications: 'getAllItems'
            }),            
        },
        mounted: function () {
            this.getAllClassifications()
        }
    
    }
</script>