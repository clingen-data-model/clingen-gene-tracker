<template>
    <div>
        <h2>Affiliation Administration</h2>
        <p class="text-muted">
            ClinGen identity, names, types, and hierarchy are synchronized externally and are read-only.
            Only the local short name can be edited here.
        </p>

        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" title="Edit Affiliation Short Name">
            <dl class="row mb-3">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ editing.name }}</dd>
                <dt class="col-sm-3">ClinGen ID</dt><dd class="col-sm-9">{{ editing.clingen_id }}</dd>
                <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ editing.type?.name || '—' }}</dd>
                <dt class="col-sm-3">Parent</dt><dd class="col-sm-9">{{ editing.parent?.name || '—' }}</dd>
            </dl>
            <b-form @submit.prevent="save">
                <b-form-group label="Short Name" label-for="affiliation-short-name">
                    <b-form-input
                        id="affiliation-short-name"
                        v-model="form.short_name"
                        :state="fieldState('short_name')"
                        :disabled="saving"
                        maxlength="15"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.short_name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        Save Short Name
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading affiliations" />
            <span class="visually-hidden">Loading affiliations</span>
        </div>
        <b-table v-else :items="affiliations" :fields="fields" responsive striped hover show-empty empty-text="No affiliations found.">
            <template #cell(short_name)="{ item }">{{ item.short_name || '—' }}</template>
            <template #cell(type)="{ item }">{{ item.type?.name || '—' }}</template>
            <template #cell(parent)="{ item }">{{ item.parent?.name || '—' }}</template>
            <template #cell(expert_panel)="{ item }">{{ item.expert_panel?.name || '—' }}</template>
            <template #cell(actions)="{ item }">
                <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">Edit Short Name</b-button>
            </template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
const canUpdate = computed(() => user.value.hasRole('admin') || user.value.hasRole('programmer'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'short_name', label: 'Short Name', sortable: true },
        { key: 'clingen_id', label: 'ClinGen ID', sortable: true },
        { key: 'type', label: 'Type' },
        { key: 'parent', label: 'Parent' },
        { key: 'expert_panel', label: 'Expert Panel' },
    ]
    if (canUpdate.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const affiliations = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ short_name: '' })
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })

function fieldState(field) { return validationErrors.value[field]?.length ? false : null }
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startEdit(affiliation) { clearMessages(); editing.value = affiliation; form.short_name = affiliation.short_name || '' }
function cancelEdit() { editing.value = null; validationErrors.value = {} }

async function loadAffiliations() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/affiliations')
        affiliations.value = response.data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load affiliations.'
    } finally {
        loading.value = false
    }
}

async function save() {
    clearMessages()
    saving.value = true
    try {
        const response = await window.axios.put(`/api/admin/affiliations/${editing.value.id}`, {
            short_name: form.short_name || null,
        })
        const index = affiliations.value.findIndex(item => item.id === response.data.id)
        if (index !== -1) affiliations.value[index] = response.data
        editing.value = null
        successMessage.value = 'Affiliation short name updated successfully.'
    } catch (error) {
        if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
        else errorMessage.value = error.response?.data?.message || 'Unable to update the affiliation.'
    } finally {
        saving.value = false
    }
}

onMounted(loadAffiliations)
</script>
