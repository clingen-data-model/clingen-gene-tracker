<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id" class="position-relative"
            :class="{'error': errors.mondo_id}"
        >
            <search-select 
                :value="updatedCuration.disease"
                @input="updateCurationDisease"
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
            <!-- <small class="text-muted" v-else>
                This precuration is linked to a <a target="gci" :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`">GCI record</a>.  Please update the MonDO ID <a target="gci" :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`">there</a>.
            </small> -->

            <!-- <mondo-alert v-if="updatedCuration.disease" :curation="updatedCuration"></mondo-alert> -->
            <curation-notification :curation="updatedCuration" :search-by-mondo="true"/>

        </b-form-group>
        or
        <b-form-group horizontal
            :class="{'error': errors.disease_entity_notes}"
        >
            <template v-slot:label>
                Disease Entity (<small>Use when no appropriate MonDO ID is available.</small>)
            </template>
            <textarea v-model="updatedCuration.disease_entity_notes" class="form-control" />
            <transition name="fade">
                <div v-if="updatedCuration.disease_entity_notes" class="alert alert-info mt-2">
                    <a href="https://github.com/monarch-initiative/mondo/issues/new/choose" target="mondo">Request a new MonDO term</a> by submitting an issue on their <a href="https://github.com/monarch-initiative/mondo">GitHub project.</a> (GitHub account required)
                </div>
            </transition>
        </b-form-group>

        <send-to-gci-button :curation="updatedCuration" @saved="emitUpdated"/>

    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import CurationNotification from './ExistingCurationNotification.vue'
    import GciLinkedMessage from '../GciLinkedMessage.vue'
    import ValidationError from '../../ValidationError.vue'
    import SearchSelect from '../../forms/SearchSelect.vue'
    import SendToGciButton from '../SendToGciButton.vue'

    export default {
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
        ],
        components: {
            ValidationError,
            CurationNotification,
            GciLinkedMessage,
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
            value: function (to, from) {
                if (this.value != this.updatedCuration) {
                    this.syncValue();
                }
            }
        },
        methods: {
            updateCurationDisease: function (value) {
                console.log(value);
                this.updatedCuration.disease = value;
                this.updatedCuration.mondo_id = value ? value.mondo_id : null;
                this.emitUpdated()
            },
            emitUpdated () {
                this.$emit('input', this.updatedCuration)
            }
       }
    }
</script>