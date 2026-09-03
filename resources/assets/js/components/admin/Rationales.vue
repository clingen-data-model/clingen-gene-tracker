<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Rationales</h2>
            <b-button v-if="canCreate" variant="primary" @click="startCreate">Add Rationale</b-button>
        </div>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit Rationale' : 'Add Rationale'">
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="rationale-name">
                    <b-form-input id="rationale-name" v-model="name" :state="nameState" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        {{ editing.id ? 'Save Changes' : 'Create Rationale' }}
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading rationales" /><span class="visually-hidden">Loading rationales</span>
        </div>
        <b-table v-else :items="items" :fields="fields" responsive striped hover show-empty empty-text="No rationales found.">
            <template #cell(actions)="{ item }">
                <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">Edit</b-button>
                <b-button v-if="canDelete" class="ms-2" size="sm" variant="outline-danger" @click="remove(item)">Delete</b-button>
            </template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
const canCreate = computed(() => user.value.hasPermission('create rationales'))
const canUpdate = computed(() => user.value.hasPermission('update rationales'))
const canDelete = computed(() => user.value.hasPermission('delete rationales'))
const fields = computed(() => {
    const values = [{ key: 'name', label: 'Name', sortable: true }]
    if (canUpdate.value || canDelete.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})
const items = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const name = ref('')
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const nameState = computed(() => validationErrors.value.name?.length ? false : null)
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })

function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startCreate() { clearMessages(); editing.value = {}; name.value = '' }
function startEdit(item) { clearMessages(); editing.value = item; name.value = item.name }
function cancelEdit() { editing.value = null; validationErrors.value = {} }

async function load() {
    loading.value = true
    try { items.value = (await window.axios.get('/api/admin/rationales')).data }
    catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load rationales.' }
    finally { loading.value = false }
}

async function save() {
    clearMessages(); saving.value = true
    try {
        if (editing.value.id) {
            const response = await window.axios.put(`/api/admin/rationales/${editing.value.id}`, { name: name.value })
            const index = items.value.findIndex(item => item.id === response.data.id)
            if (index !== -1) items.value[index] = response.data
            successMessage.value = 'Rationale updated successfully.'
        } else {
            const response = await window.axios.post('/api/admin/rationales', { name: name.value })
            items.value.push(response.data); items.value.sort((a, b) => a.name.localeCompare(b.name))
            successMessage.value = 'Rationale created successfully.'
        }
        editing.value = null
    } catch (error) {
        if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
        else errorMessage.value = error.response?.data?.message || 'Unable to save the rationale.'
    } finally { saving.value = false }
}

async function remove(item) {
    if (!window.confirm(`Delete rationale "${item.name}"?`)) return
    clearMessages()
    try {
        await window.axios.delete(`/api/admin/rationales/${item.id}`)
        items.value = items.value.filter(value => value.id !== item.id)
        successMessage.value = 'Rationale deleted successfully.'
    } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to delete the rationale.' }
}

onMounted(load)
</script>
