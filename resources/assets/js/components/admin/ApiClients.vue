<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>API Clients</h2>
            <b-button variant="primary" @click="startCreate">Add API Client</b-button>
        </div>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>
        <b-alert v-if="plainTextToken" variant="warning" show>
            <strong>Copy this token now. It cannot be retrieved later.</strong>
            <code class="d-block text-break my-2">{{ plainTextToken }}</code>
            <b-button size="sm" variant="outline-dark" @click="copyToken">Copy token</b-button>
            <b-button size="sm" variant="link" @click="plainTextToken = ''">Dismiss</b-button>
        </b-alert>

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit API Client' : 'Add API Client'">
            <b-form @submit.prevent="saveClient">
                <b-form-group label="Name" label-for="api-client-name">
                    <b-form-input id="api-client-name" v-model="form.name" :state="fieldState('name')" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">{{ message }}</b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="mt-3" label="Contact Email" label-for="api-client-email">
                    <b-form-input id="api-client-email" v-model="form.contact_email" type="email" :state="fieldState('contact_email')" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.contact_email || []" :key="message">{{ message }}</b-form-invalid-feedback>
                </b-form-group>
                <p v-if="editing.id" class="form-text">Internal UUID: {{ editing.uuid }} (read-only)</p>
                <div class="mt-3 d-flex gap-2">
                    <b-button type="submit" variant="primary" :disabled="saving">{{ editing.id ? 'Save Changes' : 'Create API Client' }}</b-button>
                    <b-button variant="secondary" :disabled="saving" @click="editing = null">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <b-card v-if="selected" class="mb-4" title="API Client Details">
            <p><strong>Name:</strong> {{ selected.name }}<br><strong>Contact:</strong> {{ selected.contact_email }}<br><strong>UUID:</strong> {{ selected.uuid }}</p>
            <b-form class="row g-2 align-items-end mb-3" @submit.prevent="createToken">
                <b-form-group class="col-md-8" label="Token Name" label-for="token-name">
                    <b-form-input id="token-name" v-model="tokenName" :state="fieldState('name')" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">{{ message }}</b-form-invalid-feedback>
                </b-form-group>
                <div class="col-md-4"><b-button type="submit" variant="primary" :disabled="saving">Create Token</b-button></div>
            </b-form>
            <b-table :items="selected.tokens || []" :fields="tokenFields" small responsive>
                <template #cell(created_at)="{ item }">{{ dateTime(item.created_at) }}</template>
                <template #cell(last_used_at)="{ item }">{{ dateTime(item.last_used_at) }}</template>
                <template #cell(actions)="{ item }"><b-button size="sm" variant="outline-danger" @click="revokeToken(item)">Revoke</b-button></template>
            </b-table>
            <p v-if="!selected.tokens?.length" class="text-muted">No active tokens.</p>
            <b-button variant="secondary" @click="selected = null">Close</b-button>
        </b-card>

        <b-table :items="clients" :fields="fields" :busy="loading" responsive striped>
            <template #table-busy><div class="text-center my-3">Loading API clients...</div></template>
            <template #cell(last_token_activity)="{ item }">{{ dateTime(item.last_token_activity) }}</template>
            <template #cell(actions)="{ item }"><div class="d-flex gap-2"><b-button size="sm" variant="outline-primary" @click="viewClient(item)">View</b-button><b-button size="sm" variant="outline-secondary" @click="startEdit(item)">Edit</b-button></div></template>
        </b-table>
        <b-pagination v-if="totalRows > perPage" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

const fields = [{ key: 'name', label: 'Name' }, { key: 'contact_email', label: 'Contact Email' }, { key: 'tokens_count', label: 'Tokens' }, { key: 'last_token_activity', label: 'Last Token Activity' }, { key: 'actions', label: 'Actions' }]
const tokenFields = [{ key: 'name', label: 'Name' }, { key: 'created_at', label: 'Created' }, { key: 'last_used_at', label: 'Last Used' }, { key: 'actions', label: 'Actions' }]
const clients = ref([]), loading = ref(true), saving = ref(false), editing = ref(null), selected = ref(null)
const form = reactive({ name: '', contact_email: '' })
const tokenName = ref(''), plainTextToken = ref(''), validationErrors = ref({}), successMessage = ref(''), errorMessage = ref('')
const currentPage = ref(1), totalRows = ref(0), perPage = 25
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })
function fieldState(field) { return validationErrors.value[field]?.length ? false : null }
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function dateTime(value) { return value ? new Date(value).toLocaleString() : '—' }
function startCreate() { clearMessages(); editing.value = {}; Object.assign(form, { name: '', contact_email: '' }) }
function startEdit(client) { clearMessages(); editing.value = client; Object.assign(form, { name: client.name, contact_email: client.contact_email }) }
async function load() { loading.value = true; try { const response = await window.axios.get('/api/admin/api-clients', { params: { page: currentPage.value, per_page: perPage } }); clients.value = response.data.data; totalRows.value = response.data.total } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load API clients.' } finally { loading.value = false } }
async function viewClient(client, reset = true) { if (reset) { clearMessages(); plainTextToken.value = '' } try { selected.value = (await window.axios.get(`/api/admin/api-clients/${client.id}`)).data } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to load the API client.' } }
async function saveClient() { clearMessages(); saving.value = true; try { const response = editing.value.id ? await window.axios.put(`/api/admin/api-clients/${editing.value.id}`, form) : await window.axios.post('/api/admin/api-clients', form); successMessage.value = `API client ${editing.value.id ? 'updated' : 'created'} successfully.`; editing.value = null; await load(); if (response.data.id) await viewClient(response.data, false) } catch (error) { if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}; else errorMessage.value = error.response?.data?.message || 'Unable to save the API client.' } finally { saving.value = false } }
async function createToken() { clearMessages(); saving.value = true; try { const response = await window.axios.post(`/api/admin/api-clients/${selected.value.id}/tokens`, { name: tokenName.value }); tokenName.value = ''; await viewClient(selected.value, false); plainTextToken.value = response.data.plain_text_token } catch (error) { if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}; else errorMessage.value = error.response?.data?.message || 'Unable to create the token.' } finally { saving.value = false } }
async function revokeToken(token) { if (!window.confirm(`Revoke token ${token.name}?`)) return; clearMessages(); try { await window.axios.delete(`/api/admin/api-clients/${selected.value.id}/tokens/${token.id}`); successMessage.value = 'Token revoked successfully.'; await viewClient(selected.value, false) } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to revoke the token.' } }
async function copyToken() { try { await navigator.clipboard.writeText(plainTextToken.value); successMessage.value = 'Token copied.' } catch { errorMessage.value = 'Unable to copy the token. Copy it manually.' } }
watch(currentPage, load)
onMounted(load)
</script>
