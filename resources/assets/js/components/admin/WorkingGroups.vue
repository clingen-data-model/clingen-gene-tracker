<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Working Group Administration</h2>
            <b-button v-if="canCreate" variant="primary" @click="startCreate">Add Working Group</b-button>
        </div>

        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit Working Group' : 'Add Working Group'">
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="working-group-name">
                    <b-form-input
                        id="working-group-name"
                        v-model="form.name"
                        :state="fieldState('name')"
                        :disabled="saving"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        {{ editing.id ? 'Save Changes' : 'Create Working Group' }}
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading working groups" />
            <span class="visually-hidden">Loading working groups</span>
        </div>

        <b-table
            v-else
            :items="workingGroups"
            :fields="fields"
            responsive
            striped
            hover
            show-empty
            empty-text="No working groups found."
        >
            <template #cell(expert_panels_count)="{ item }">{{ item.expert_panels_count || 0 }}</template>
            <template #cell(actions)="{ item }">
                <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">Edit</b-button>
                <b-button
                    v-if="canDelete"
                    class="ms-2"
                    size="sm"
                    variant="outline-danger"
                    @click="remove(item)"
                >
                    Delete
                </b-button>
            </template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
const canCreate = computed(() => user.value.hasPermission('create working-groups'))
const canUpdate = computed(() => user.value.hasPermission('update working-groups'))
const canDelete = computed(() => user.value.hasPermission('delete working-groups'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'expert_panels_count', label: 'Expert Panels', sortable: true },
    ]
    if (canUpdate.value || canDelete.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const workingGroups = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ name: '' })
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({
    get: () => Boolean(successMessage.value),
    set: value => { if (!value) successMessage.value = '' },
})
const showError = computed({
    get: () => Boolean(errorMessage.value),
    set: value => { if (!value) errorMessage.value = '' },
})

function fieldState(field) {
    return validationErrors.value[field]?.length ? false : null
}

function clearMessages() {
    validationErrors.value = {}
    successMessage.value = ''
    errorMessage.value = ''
}

async function loadWorkingGroups() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/working-groups')
        workingGroups.value = response.data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load working groups.'
    } finally {
        loading.value = false
    }
}

function startCreate() {
    clearMessages()
    editing.value = {}
    form.name = ''
}

function startEdit(workingGroup) {
    clearMessages()
    editing.value = workingGroup
    form.name = workingGroup.name
}

function cancelEdit() {
    editing.value = null
    validationErrors.value = {}
}

async function save() {
    clearMessages()
    saving.value = true
    try {
        if (editing.value.id) {
            const response = await window.axios.put(`/api/admin/working-groups/${editing.value.id}`, form)
            const index = workingGroups.value.findIndex(item => item.id === response.data.id)
            if (index !== -1) workingGroups.value[index] = response.data
            successMessage.value = 'Working group updated successfully.'
        } else {
            const response = await window.axios.post('/api/admin/working-groups', form)
            workingGroups.value.push({ ...response.data, expert_panels_count: 0 })
            workingGroups.value.sort((left, right) => left.name.localeCompare(right.name))
            successMessage.value = 'Working group created successfully.'
        }
        editing.value = null
    } catch (error) {
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors || {}
        } else {
            errorMessage.value = error.response?.data?.message || 'Unable to save the working group.'
        }
    } finally {
        saving.value = false
    }
}

async function remove(workingGroup) {
    if (!window.confirm(`Delete working group "${workingGroup.name}"?`)) return

    clearMessages()
    try {
        await window.axios.delete(`/api/admin/working-groups/${workingGroup.id}`)
        workingGroups.value = workingGroups.value.filter(item => item.id !== workingGroup.id)
        successMessage.value = 'Working group deleted successfully.'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to delete the working group.'
    }
}

onMounted(loadWorkingGroups)
</script>
