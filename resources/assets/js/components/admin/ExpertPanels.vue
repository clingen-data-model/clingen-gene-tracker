<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Expert Panel Administration</h2>
            <b-button v-if="canCreate" variant="primary" @click="startCreate">Add Expert Panel</b-button>
        </div>

        <p class="text-muted">
            Affiliation identity is managed separately and is read-only here. Memberships are managed through users.
        </p>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit Expert Panel' : 'Add Expert Panel'">
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="expert-panel-name">
                    <b-form-input id="expert-panel-name" v-model="form.name" :state="fieldState('name')" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="mt-3" label="Working Group" label-for="expert-panel-working-group">
                    <b-form-select
                        id="expert-panel-working-group"
                        v-model="form.working_group_id"
                        :options="workingGroupOptions"
                        :state="fieldState('working_group_id')"
                        :disabled="saving"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.working_group_id || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group v-if="editing.id" class="mt-3" label="Affiliation">
                    <b-form-input :model-value="affiliationLabel(editing.affiliation)" disabled />
                    <div class="form-text">Affiliation linkage cannot be changed from Expert Panel administration.</div>
                </b-form-group>
                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        {{ editing.id ? 'Save Changes' : 'Create Expert Panel' }}
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading expert panels" />
            <span class="visually-hidden">Loading expert panels</span>
        </div>
        <b-table v-else :items="expertPanels" :fields="fields" responsive striped hover show-empty empty-text="No expert panels found.">
            <template #cell(working_group)="{ item }">{{ item.working_group?.name || '—' }}</template>
            <template #cell(affiliation)="{ item }">{{ affiliationLabel(item.affiliation) }}</template>
            <template #cell(curations_count)="{ item }">{{ item.curations_count || 0 }}</template>
            <template #cell(users_count)="{ item }">{{ item.users_count || 0 }}</template>
            <template #cell(actions)="{ item }">
                <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">Edit</b-button>
            </template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
const canCreate = computed(() => user.value.hasPermission('create expert-panels'))
const canUpdate = computed(() => user.value.hasPermission('update expert-panels'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'working_group', label: 'Working Group', sortable: true },
        { key: 'affiliation', label: 'Affiliation' },
        { key: 'curations_count', label: 'Curations', sortable: true },
        { key: 'users_count', label: 'Members', sortable: true },
    ]
    if (canUpdate.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const expertPanels = ref([])
const workingGroups = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ name: '', working_group_id: null })
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })
const workingGroupOptions = computed(() => [
    { value: null, text: 'No working group' },
    ...workingGroups.value.map(group => ({ value: group.id, text: group.name })),
])

function affiliationLabel(affiliation) {
    if (!affiliation) return '—'
    const name = affiliation.name || affiliation.short_name || 'Affiliation'
    return affiliation.clingen_id ? `${name} (${affiliation.clingen_id})` : name
}
function fieldState(field) { return validationErrors.value[field]?.length ? false : null }
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startCreate() { clearMessages(); editing.value = {}; form.name = ''; form.working_group_id = null }
function startEdit(panel) { clearMessages(); editing.value = panel; form.name = panel.name; form.working_group_id = panel.working_group_id }
function cancelEdit() { editing.value = null; validationErrors.value = {} }

async function loadData() {
    loading.value = true
    try {
        const [panelsResponse, groupsResponse] = await Promise.all([
            window.axios.get('/api/admin/expert-panels'),
            window.axios.get('/api/working-groups'),
        ])
        expertPanels.value = panelsResponse.data
        workingGroups.value = groupsResponse.data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load expert panels.'
    } finally {
        loading.value = false
    }
}

async function save() {
    clearMessages()
    saving.value = true
    const payload = { name: form.name, working_group_id: form.working_group_id ?? null }
    try {
        if (editing.value.id) {
            const response = await window.axios.put(`/api/admin/expert-panels/${editing.value.id}`, payload)
            const index = expertPanels.value.findIndex(item => item.id === response.data.id)
            if (index !== -1) expertPanels.value[index] = response.data
            successMessage.value = 'Expert panel updated successfully.'
        } else {
            const response = await window.axios.post('/api/admin/expert-panels', payload)
            expertPanels.value.push(response.data)
            expertPanels.value.sort((left, right) => left.name.localeCompare(right.name))
            successMessage.value = 'Expert panel created successfully.'
        }
        editing.value = null
    } catch (error) {
        if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
        else errorMessage.value = error.response?.data?.message || 'Unable to save the expert panel.'
    } finally {
        saving.value = false
    }
}

onMounted(loadData)
</script>
