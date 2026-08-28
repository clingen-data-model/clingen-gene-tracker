<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id" class="position-relative"
            :class="{'error': errors.mondo_id}"
        >
            <search-select 
                :model-value="updatedCuration.disease"
                @update:model-value="updateCurationDisease"
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
<script setup>
import useCurationForm from '../../../composables/useCurationForm'
import CurationNotification from './ExistingCurationNotification.vue'
import ValidationError from '../../ValidationError.vue'
import SearchSelect from '../../forms/SearchSelect.vue'
import SendToGciButton from '../SendToGciButton.vue'

const props = defineProps(['modelValue', 'errors'])
const emit = defineEmits(['update:modelValue'])
const page = 'mondo'
const { updatedCuration } = useCurationForm(props, emit, page, {})

async function searchMondo(searchText) {
    return await window.axios.get('/api/diseases/search?query_string='+searchText)
        .then(response => {
            return response.data
        })
}

function updateCurationDisease(value) {
    console.log(value)
    updatedCuration.value.disease = value
    updatedCuration.value.mondo_id = value ? value.mondo_id : null
}

function emitUpdated() {
    emit('update:modelValue', updatedCuration.value)
}
</script>
