<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Curation Types</h2>
            <b-button v-if="canCreate" variant="primary" @click="startCreate">
                Add Curation Type
            </b-button>
        </div>

        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit Curation Type' : 'Add Curation Type'">
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="curation-type-name">
                    <b-form-input
                        id="curation-type-name"
                        v-model="form.name"
                        :state="fieldState('name')"
                        :disabled="saving"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="mt-3" label="Description" label-for="curation-type-description">
                    <b-form-textarea
                        id="curation-type-description"
                        v-model="form.description"
                        :state="fieldState('description')"
                        :disabled="saving"
                        rows="3"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.description || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>

                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        {{ editing.id ? 'Save Changes' : 'Create Curation Type' }}
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">
                        Cancel
                    </b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading curation types" />
            <span class="visually-hidden">Loading curation types</span>
        </div>

        <b-table
            v-else
            :items="curationTypes"
            :fields="fields"
            responsive
            striped
            hover
            show-empty
            empty-text="No curation types found."
        >
            <template #cell(actions)="{ item }">
                <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">
                    Edit
                </b-button>
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
const canCreate = computed(() => user.value.hasPermission('create curation-types'))
const canUpdate = computed(() => user.value.hasPermission('update curation-types'))
const canDelete = computed(() => user.value.hasPermission('delete curation-types'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'description', label: 'Description' },
    ]

    if (canUpdate.value || canDelete.value) {
        values.push({ key: 'actions', label: 'Actions' })
    }

    return values
})

const curationTypes = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ name: '', description: '' })
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
    errorMessage.value = ''
    successMessage.value = ''
}

async function loadCurationTypes() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/curation-types')
        curationTypes.value = response.data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load curation types.'
    } finally {
        loading.value = false
    }
}

function startCreate() {
    clearMessages()
    editing.value = {}
    form.name = ''
    form.description = ''
}

function startEdit(curationType) {
    clearMessages()
    editing.value = curationType
    form.name = curationType.name
    form.description = curationType.description || ''
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
            const response = await window.axios.put(`/api/admin/curation-types/${editing.value.id}`, form)
            const index = curationTypes.value.findIndex(item => item.id === response.data.id)
            if (index !== -1) curationTypes.value[index] = response.data
            successMessage.value = 'Curation type updated successfully.'
        } else {
            const response = await window.axios.post('/api/admin/curation-types', form)
            curationTypes.value.push(response.data)
            curationTypes.value.sort((left, right) => left.name.localeCompare(right.name))
            successMessage.value = 'Curation type created successfully.'
        }
        editing.value = null
    } catch (error) {
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors || {}
        } else {
            errorMessage.value = error.response?.data?.message || 'Unable to save the curation type.'
        }
    } finally {
        saving.value = false
    }
}

async function remove(curationType) {
    if (!window.confirm(`Delete curation type "${curationType.name}"?`)) return

    clearMessages()
    try {
        await window.axios.delete(`/api/admin/curation-types/${curationType.id}`)
        curationTypes.value = curationTypes.value.filter(item => item.id !== curationType.id)
        successMessage.value = 'Curation type deleted successfully.'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to delete the curation type.'
    }
}

onMounted(loadCurationTypes)
</script>
