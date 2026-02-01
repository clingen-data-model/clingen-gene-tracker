<style></style>

<template>
    <div class="component-container w-50">
        <classification-history :curation="updatedCuration"></classification-history>
        <div class="alert alert-secondary">
            Classifications must be added to a curation via the GCI.
        </div>

    </div>
</template>

<script>
    import { mapState, mapActions } from 'pinia'
    import { useClassificationsStore } from '../../../stores/classifications'
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
            ...mapState(useClassificationsStore, {
               classifications: 'Items',
            }),
        },
        methods: {
            ...mapActions(useClassificationsStore, {
                getAllClassifications: 'getAllItems'
            }),
        },
        mounted: function () {
            this.getAllClassifications()
        }

    }
</script>
