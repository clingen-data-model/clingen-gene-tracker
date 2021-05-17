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
    import CurationNotifications from './ExistingCurationNotification'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import DateField from '../../DateField'
    import ValidationError from '../../ValidationError'
    import ClassificationHistory from '../ClassificationHistory'

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