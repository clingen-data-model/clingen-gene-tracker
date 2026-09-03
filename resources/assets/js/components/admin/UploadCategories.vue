<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3"><h2>Upload Categories</h2><b-button variant="primary" @click="startCreate">Add Upload Category</b-button></div>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>
        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit Upload Category' : 'Add Upload Category'">
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="upload-category-name">
                    <b-form-input id="upload-category-name" v-model="name" :state="nameState" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">{{ message }}</b-form-invalid-feedback>
                </b-form-group>
                <div class="mt-3"><b-button type="submit" variant="primary" :disabled="saving"><b-spinner v-if="saving" small aria-label="Saving" /> {{ editing.id ? 'Save Changes' : 'Create Upload Category' }}</b-button><b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button></div>
            </b-form>
        </b-card>
        <div v-if="loading" class="text-center py-5" role="status"><b-spinner label="Loading upload categories" /><span class="visually-hidden">Loading upload categories</span></div>
        <b-table v-else :items="items" :fields="fields" responsive striped hover show-empty empty-text="No upload categories found.">
            <template #cell(actions)="{ item }"><b-button size="sm" variant="outline-primary" @click="startEdit(item)">Edit</b-button><b-button class="ms-2" size="sm" variant="outline-danger" @click="remove(item)">Delete</b-button></template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
const fields = [{ key: 'name', label: 'Name', sortable: true }, { key: 'actions', label: 'Actions' }]
const items = ref([]); const loading = ref(true); const saving = ref(false); const editing = ref(null); const name = ref('')
const validationErrors = ref({}); const successMessage = ref(''); const errorMessage = ref('')
const nameState = computed(() => validationErrors.value.name?.length ? false : null)
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startCreate() { clearMessages(); editing.value = {}; name.value = '' }
function startEdit(item) { clearMessages(); editing.value = item; name.value = item.name }
function cancelEdit() { editing.value = null; validationErrors.value = {} }
async function load() { loading.value = true; try { items.value = (await window.axios.get('/api/admin/upload-categories')).data } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load upload categories.' } finally { loading.value = false } }
async function save() {
    clearMessages(); saving.value = true
    try {
        if (editing.value.id) {
            const response = await window.axios.put(`/api/admin/upload-categories/${editing.value.id}`, { name: name.value })
            const index = items.value.findIndex(item => item.id === response.data.id); if (index !== -1) items.value[index] = response.data
            successMessage.value = 'Upload category updated successfully.'
        } else {
            const response = await window.axios.post('/api/admin/upload-categories', { name: name.value })
            items.value.push(response.data); items.value.sort((a, b) => a.name.localeCompare(b.name)); successMessage.value = 'Upload category created successfully.'
        }
        editing.value = null
    } catch (error) { if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}; else errorMessage.value = error.response?.data?.message || 'Unable to save the upload category.' }
    finally { saving.value = false }
}
async function remove(item) {
    if (!window.confirm(`Delete upload category "${item.name}"?`)) return
    clearMessages(); try { await window.axios.delete(`/api/admin/upload-categories/${item.id}`); items.value = items.value.filter(value => value.id !== item.id); successMessage.value = 'Upload category deleted successfully.' } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to delete the upload category.' }
}
onMounted(load)
</script>
