<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id" class="position-relative"
            :class="{'error': errors.mondo_id}"
        >
            <search-select 
                v-model="updatedCuration.disease" 
                :search-function="searchMondo"
                style="z-index: 2"
                placeholder="MonDO ID or name"
            >
                <template v-slot:selection-label="{selection}">
                    <div v-if="typeof selection == 'object'">
                        {{selection.mondo_id}} - {{selection.name}}
                    </div>
                    <div v-else>{{selection}}</div>
                </template>
                <template v-slot:option="{option}">
                    <div v-if="typeof option == 'object'">
                        {{option.mondo_id}} - {{option.name}}
                    </div>
                    <div v-else>
                        {{option}}
                    </div>
                </template>
            </search-select>
            <validation-error :messages="errors.mondo_id"></validation-error>
            <small class="text-muted">Alternatively, refer to <a href="https://www.ebi.ac.uk/ols/ontologies/mondo" target="mondo">MonDO</a> for a valid MonDO ID</small>
            <mondo-alert :curation="updatedCuration"></mondo-alert>
        </b-form-group>
        or
        <b-form-group horizontal label="Disease Entity"
            :class="{'error': errors.disease_entity_notes}"
        >
            <textarea v-model="updatedCuration.disease_entity_notes" class="form-control"></textarea>
            <small>Use when no appropriate MonDO ID is available</small>
        </b-form-group>
    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import MondoAlert from './ExistingCurationMondoAlert'
    import ValidationError from '../../ValidationError'
    import SearchSelect from '../../forms/SearchSelect'

    export default {
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
        ],
        components: {
            MondoAlert,
            ValidationError,
            SearchSelect
        },
        data: function () {
            return {
                page: 'mondo',
                updatedCuration: {},
                selection: 'eat',
                searchMondo: async (searchText) => {
                    return await window.axios.get('/api/diseases/search?query_string='+searchText)
                        .then(response => {
                            console.info('mondo options', response.data);
                            return response.data;
                        });
                }
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