<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id">
            <input 
                type="text" 
                v-model="updatedCuration.mondo_id" 
                class="form-control"
                :class="{'border-danger': errors.mondo_id}"
                placeholder="MONDO:0001158"
            >
            <validation-error :messages="errors.mondo_id"></validation-error>
            <small class="text-muted">Refer to <a href="https://www.ebi.ac.uk/ols/ontologies/mondo" target="mondo">MonDO</a> for a valid MonDO ID</small>
            <mondo-alert :curation="updatedCuration"></mondo-alert>
        </b-form-group>
        or
        <b-form-group horizontal label="Disease Entity">
            <textarea v-model="updatedCuration.disease_entity_notes" class="form-control"></textarea>
            <small>Use when no appropriate MonDO ID is available</small>
        </b-form-group>
    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import MondoAlert from './ExistingCurationMondoAlert'
    import ValidationError from '../../ValidationError'

    export default {
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
        ],
        components: {
            MondoAlert,
            ValidationError,
        },
        data: function () {
            return {
                page: 'mondo',
                updatedCuration: {}
            }
        },
        watch: {
            updatedCuration: function () {
                this.$emit('input', this.updatedCuration)
            },
            value: function (to, from) {
                if (this.value != this.updatedCuration) {
                    this.syncValue();
                }
            }
        }
    }
</script>