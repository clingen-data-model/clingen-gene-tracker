<style></style>

<template>
    <div class="component-container">
        <classification-form v-model="updatedCuration" class="mt-1"></classification-form>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationNotifications from './ExistingCurationNotification'
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import DateField from '../../DateField'
    import ValidationError from '../../ValidationError'
    import ClassificationForm from './ClassificationForm'
    import moment from 'moment'

    export default {
        components: {
            CurationNotifications,
            DateField,
            ValidationError,
            ClassificationForm
        },
        mixins: [
            curationFormMixin,
        ],
        data() {
            return {}
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