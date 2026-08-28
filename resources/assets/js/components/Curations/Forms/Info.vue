<style>
    .small-calendar{
        font-size: .8em;
        width: 226px;
    }
    .small-calendar .cell {
        width: 32px;
        height: 32px;
        line-height: 32px;
    }
</style>
<template>
    <div id="curation-info-fields" style="position: relative">
        
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC Gene Symbol"
            label-for="gene-symbol-input"
            :class="{'error': fieldError('gene_symbol')}"
        >
            <b-form-input
                id="gene-symbol-input"
                type="text"
                :model-value="updatedCuration.gene_symbol"
                @update:model-value="handleGeneInput"
                required
                placeholder="ATK-1"
                :disabled="hasGdmUuid() || isArchivedReadOnly"
            />
            <gci-linked-message :curation="updatedCuration" attribute-label="the gene"></gci-linked-message>

            <validation-error :messages="errors.gene_symbol"></validation-error>
        </b-form-group>

        <curation-notifications :curation="updatedCuration"></curation-notifications>

        <b-form-group horizontal label="Mode of Inheritance" label-for="moi_input"
            :class="{'error': fieldError('moi_id')}"
        >
            <b-form-select :model-value="updatedCuration.moi_id"
                @update:model-value="updatedCuration.moi_id = $event"
                id="moi_input"
                :disabled="hasGdmUuid() || isArchivedReadOnly"
            >
                <option :value="null">Select...</option>
                <option v-for="moi in mois" :key="moi.id"
                    :value="moi.id"
                >
                    {{`${moi.name} (${moi.hp_id})`}}
                </option>
            </b-form-select>
            <validation-error :messages="errors.moi_id"></validation-error>
            <gci-linked-message :curation="updatedCuration" attribute-label="the mode of inheritance"></gci-linked-message>

        </b-form-group>
        
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select"
            :class="{'error': fieldError('expert_panel_id')}"
        >
            <b-form-select 
                id="expert-panel-select" 
                :model-value="updatedCuration.expert_panel_id"
                @update:model-value="updatedCuration.expert_panel_id = $event"
                :disabled="!canUpdateExpertPanel || isArchivedReadOnly"
            >
                <option :value="null">Select...</option>
                <option v-for="panel in panelOptions" 
                    :value="panel.id"
                    :key="panel.id"
                >
                    {{panel.name}}
                </option>
            </b-form-select>
            <small class="text-muted" v-if="!canUpdateExpertPanel && $store.state.features.transferEnabled">
                To change the expert panel use click the "Transfer Curation" button.
            </small>
            <validation-error :messages="errors.expert_panel_id"></validation-error>
        </b-form-group>
    
        <b-form-group horizontal 
            id="curator-select-group" 
            label="Curator" 
            label-for="curator-select"
            :class="{'error': fieldError('curator_id')}"
        >
            <b-form-select id="curator-select" v-model="updatedCuration.curator_id" :disabled="isArchivedReadOnly">
                <option :value="null">Select...</option>
                <option v-for="curator in panelCurators" 
                    :key="curator.id"
                    :value="curator.id"
                >
                    {{curator.name}}
                </option>
            </b-form-select>
            <validation-error :messages="errors.curator_id"></validation-error>
        </b-form-group>
    
        <b-form-group horizontal label="Notes" label-for="notes-field"
            :class="{'error': fieldError('notes')}"
        >
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedCuration.curation_notes" :disabled="isArchivedReadOnly"></textarea>
            <validation-error :messages="errors.curation_notes"></validation-error>
        </b-form-group>

        <b-form-group horizontal label="Status" label-for="curation_status_id" v-if="updatedCuration && updatedCuration.curation_statuses">
            <status-form
                :model-value="updatedCuration"
                @update:model-value="updatedCuration = $event"
                class="mt-1"
            ></status-form>
        </b-form-group>

        <div v-if="isArchived" class="alert alert-warning mt-3">
            <strong>This curation is archived.</strong>
            <div v-if="updatedCuration.archive_reason" class="mt-1">
                <strong>Reason:</strong> {{ updatedCuration.archive_reason }}
            </div>
            <div v-if="updatedCuration.gcex_url && updatedCuration.gcex_url.startsWith('https://')" class="mt-1">
                <strong>GCEx URL:</strong>
                <a :href="updatedCuration.gcex_url" target="_blank">{{ updatedCuration.gcex_url }}</a>
            </div>
            <div v-if="isArchivedReadOnly" class="mt-2">
                This curation is read-only. If it needs to be updated or archived/unarchived, please contact an administrator.
            </div>
        </div>

        <div v-if="updatedCuration && updatedCuration.id" class="mt-3">
            <div v-if="!canManageArchive" class="alert alert-warning mt-2 mb-0">
                Archiving is managed by administrators. Please contact support if this curation should be archived.
            </div>
            <template v-else>
                <button
                    v-if="!isArchived"
                    type="button"
                    class="btn btn-outline-secondary btn-sm mt-2"
                    @click="toggleArchiveFields"
                >
                    Manage Archive
                </button>
                <button
                    v-else
                    type="button"
                    class="btn btn-secondary mt-2"
                    :disabled="archiveSaving"
                    @click="unarchiveCuration"
                >
                    Unarchive
                </button>
            </template>
        </div>

        <div v-if="showArchiveFields && canManageArchive" class="card mt-3">
            <div v-if="showArchiveGciWarning" class="alert alert-warning mb-3">
                This curation can still be archived in GeneTracker, but the archive update will not be sent to GCI because the record does not currently have a linked GCI UUID or enough identifying data (gene, disease, and mode of inheritance).
            </div>
            <div class="card-body">
                <b-form-group horizontal label="Archive Reason" label-for="archive_reason">
                    <textarea
                        id="archive_reason"
                        class="form-control"
                        v-model="archiveForm.archive_reason"
                        rows="3"
                    ></textarea>
                </b-form-group>

                <b-form-group horizontal label="GCEx URL" label-for="gcex_url">
                    <b-form-input
                        id="gcex_url"
                        v-model="archiveForm.gcex_url"
                        type="text"
                    ></b-form-input>
                </b-form-group>

                <div class="mt-3">
                     <button
                        type="button"
                        class="btn btn-outline-secondary ms-2"
                        :disabled="archiveSaving"
                        @click="toggleArchiveFields"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-warning"
                        :disabled="archiveSaving"
                        @click="archiveCuration"
                    >
                        Archive
                    </button>                    
                </div>
            </div>            
        </div>

        <archived-curation-links
            :model-value="updatedCuration"
            :editable="!updatedCuration.is_archived && user.canEditCuration(updatedCuration)"
            @update:model-value="updatedCuration = $event"
        />

        <br />
        <div class="alert alert-info mt-3" v-if="canEditGdmUuid">
            <h5>
                Advanced Info
                <small class="text-muted float-end"><small>
                    You are seeing this b/c you are a trusted user.
                    <br>
                    Only use these fields if you know what you're doing.
                </small></small>
            </h5>
            <hr>
            <b-form-group
                horizontal 
                label="GCI UUID" 
                label-for="gdm_uuid"
                :class="{'error': fieldError('gdm_uuid')}"
            >
                <b-form-input 
                    id="gdm_uuid" 
                    v-model="updatedCuration.gdm_uuid"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    :disabled="isArchivedReadOnly"
                ></b-form-input>
                <small>
                    <a :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`" 
                        v-if="updatedCuration.gdm_uuid"
                        target="gci"
                    >
                        GCI Record
                    </a>
                </small>
                <validation-error :messages="errors.gdm_uuid"></validation-error>
            </b-form-group>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import CurationNotifications from './ExistingCurationNotification.vue'
import ValidationError from '../../ValidationError.vue'
import StatusForm from './StatusForm.vue'
import ArchivedCurationLinks from './ArchivedCurationLinks.vue'
import useCurationForm from '../../../composables/useCurationForm'

const props = defineProps(['modelValue', 'errors'])
const emit = defineEmits(['update:modelValue'])

const store = useStore()
const { updatedCuration } = useCurationForm(props, emit, 'info')

const showArchiveFields = ref(false)
const archiveForm = reactive({
    archive_reason: '',
    gcex_url: '',
})
const archiveSaving = ref(false)

const user = computed(() => store.getters.getUser)
const mois = computed(() => store.getters['mois/Items'])
const panels = computed(() => store.getters['panels/Items'])
const curators = computed(() => store.getters['users/getCurators'])

const panelOptions = computed(() => {
    return panels.value
        .filter(panel => user.value.canSelectExpertPanel(panel))
        .sort((a, b) => a.name.localeCompare(b.name))
})

const panelCurators = computed(() => {
    const availableCurators = curators.value.filter(curator => {
        return (
            curator.expert_panels
            && curator.expert_panels.find(panel => panel.id == updatedCuration.value.expert_panel_id)
        )
    })

    if (availableCurators && availableCurators.length == 1) {
        updatedCuration.value.curator_id = availableCurators[0].id
    } else if (availableCurators && availableCurators.length > 0) {
        updatedCuration.value.curator_id = updatedCuration.value.curator_id ? updatedCuration.value.curator_id : null
    } else {
        updatedCuration.value.curator_id = null
    }

    return availableCurators
})

const canManageArchive = computed(() => {
    return user.value && user.value.canManageArchive()
})

const isArchived = computed(() => {
    return Boolean(updatedCuration.value && updatedCuration.value.is_archived)
})

const isArchivedReadOnly = computed(() => {
    return isArchived.value && !canManageArchive.value
})

const canUpdateExpertPanel = computed(() => {
    if (isArchivedReadOnly.value) { return false }
    return !Boolean(updatedCuration.value && updatedCuration.value.expert_panel_id && updatedCuration.value.id)
})

const canEditGdmUuid = computed(() => {
    if (isArchivedReadOnly.value || !updatedCuration.value.expert_panel) {
        return false
    }
    return user.value.hasPermission('update curation gdm_uuid')
        || user.value.isPanelCoordinator(updatedCuration.value.expert_panel)
        || user.value.canEditPanelCurations(updatedCuration.value.expert_panel)
})

const hasGciIdentification = computed(() => {
    return Boolean(
        updatedCuration.value?.gdm_uuid
        || (updatedCuration.value?.hgnc_id && updatedCuration.value?.mondo_id && updatedCuration.value?.moi_id)
    )
})

const showArchiveGciWarning = computed(() => {
    return showArchiveFields.value && !hasGciIdentification.value
})

function fieldError(field) {
    return props.errors && props.errors[field] && props.errors[field].length > 0
}

function hasGdmUuid() {
    return updatedCuration.value.gdm_uuid !== null && typeof updatedCuration.value.gdm_uuid !== 'undefined'
}

function toggleArchiveFields() {
    showArchiveFields.value = !showArchiveFields.value
    if (showArchiveFields.value) {
        archiveForm.archive_reason = updatedCuration.value.archive_reason || ''
        archiveForm.gcex_url = updatedCuration.value.gcex_url || ''
    } else {
        archiveForm.archive_reason = ''
        archiveForm.gcex_url = ''
    }
}

async function archiveCuration() {
    archiveSaving.value = true
    try {
        const response = await axios.patch(`/api/curations/${updatedCuration.value.id}/archive`, archiveForm)
        updatedCuration.value = response.data
        showArchiveFields.value = false
    } finally {
        archiveSaving.value = false
    }
}

async function unarchiveCuration() {
    archiveSaving.value = true
    try {
        const response = await axios.patch(`/api/curations/${updatedCuration.value.id}/unarchive`)
        updatedCuration.value = response.data
        showArchiveFields.value = false
    } finally {
        archiveSaving.value = false
    }
}

function handleGeneInput(value) {
    updatedCuration.value.gene_symbol = value
}

onMounted(() => {
    store.dispatch('panels/getAllItems')
    store.dispatch('users/getAllItems')
    store.dispatch('mois/getAllItems')
})
</script>
