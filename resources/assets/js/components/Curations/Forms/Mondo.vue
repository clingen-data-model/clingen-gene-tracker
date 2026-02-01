<style></style>
<template>
    <div>
        <div class="mb-3 position-relative"
            :class="{'error': errors.mondo_id}"
        >
            <label>MonDO ID</label>
            <search-select
                :modelValue="updatedCuration.disease"
                @update:modelValue="updateCurationDisease"
                :search-function="searchMondo"
                style="z-index: 2"
                placeholder="MonDO ID or name"
                :disabled="(updatedCuration.gdm_uuid !== null)"
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
            <gci-linked-message :curation="updatedCuration" attribute-label="MonDO ID">
                <small class="text-muted">
                    Alternatively, refer to <a href="https://www.ebi.ac.uk/ols/ontologies/mondo" target="mondo">MonDO</a> for a valid MonDO ID
                </small>
            </gci-linked-message>

            <curation-notification :curation="updatedCuration" :search-by-mondo="true"/>

        </div>
        or
        <div class="mb-3"
            :class="{'error': errors.disease_entity_notes}"
        >
            <label>Disease Entity (<small>Use when no appropriate MonDO ID is available.</small>)</label>
            <textarea v-model="updatedCuration.disease_entity_notes" class="form-control" />
            <transition name="fade">
                <div v-if="updatedCuration.disease_entity_notes" class="alert alert-info mt-2">
                    <a href="https://github.com/monarch-initiative/mondo/issues/new/choose" target="mondo">Request a new MonDO term</a> by submitting an issue on their <a href="https://github.com/monarch-initiative/mondo">GitHub project.</a> (GitHub account required)
                </div>
            </transition>
        </div>

        <send-to-gci-button :curation="updatedCuration" @saved="emitUpdated"/>

    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import CurationNotification from './ExistingCurationNotification.vue'
    import ValidationError from '../../ValidationError.vue'
    import SearchSelect from '../../forms/SearchSelect.vue'
    import SendToGciButton from '../SendToGciButton.vue'

    export default {
        mixins: [
            curationFormMixin,
        ],
        components: {
            ValidationError,
            CurationNotification,
            SearchSelect,
            SendToGciButton,
        },
        data: function () {
            return {
                page: 'mondo',
                updatedCuration: {},
                selection: 'eat',
                searchMondo: async (searchText) => {
                    return await window.axios.get('/api/diseases/search?query_string='+searchText)
                        .then(response => {
                            return response.data;
                        });
                }
            }
        },
        watch: {
            updatedCuration: function () {
                this.emitUpdated()
            },
            modelValue: function (to, from) {
                if (this.modelValue != this.updatedCuration) {
                    this.syncValue();
                }
            }
        },
        methods: {
            updateCurationDisease: function (value) {
                this.updatedCuration.disease = value;
                this.updatedCuration.mondo_id = value ? value.mondo_id : null;
                this.emitUpdated()
            },
            emitUpdated () {
                this.$emit('update:modelValue', this.updatedCuration)
            }
       }
    }
</script>
