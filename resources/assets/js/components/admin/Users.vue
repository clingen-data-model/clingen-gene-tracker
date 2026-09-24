<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>User Administration</h2>
            <b-button v-if="canCreate" variant="primary" @click="startCreate">Add User</b-button>
        </div>

        <p class="text-muted">
            Manage Expert Panel memberships when editing a user. Affiliation memberships are managed separately.
        </p>
        <AdminSearch placeholder="Name or email" @search="applySearch" />
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <RevisionHistory
            v-if="revisionRecord"
            :endpoint="`/api/admin/users/${revisionRecord.id}/revisions`"
            :name="revisionRecord.name"
            @close="revisionRecord = null"
        />

        <b-card v-if="editing" class="mb-4" :title="editing.id ? 'Edit User' : 'Create User'">
            <p v-if="!editing.id" class="text-muted">The user will receive welcome instructions to set their password.</p>
            <b-form @submit.prevent="save">
                <b-form-group label="Name" label-for="user-name">
                    <b-form-input id="user-name" v-model="form.name" :state="fieldState('name')" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.name || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="mt-3" label="Email" label-for="user-email">
                    <b-form-input id="user-email" v-model="form.email" type="email" :state="fieldState('email')" :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.email || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="mt-3" label="Roles" label-for="user-roles">
                    <b-form-select id="user-roles" v-model="form.role_ids" :options="roleOptions" multiple :disabled="saving" />
                    <b-form-invalid-feedback v-for="message in validationErrors.role_ids || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="mt-3" label="Extra Permissions" label-for="user-permissions">
                    <b-form-select id="user-permissions" v-model="form.permission_ids" :options="permissionOptions" multiple :select-size="8" :disabled="saving" />
                    <div class="form-text">These permissions are assigned directly in addition to permissions inherited from roles.</div>
                </b-form-group>
                <ExpertPanelMemberships v-model="form.expert_panels" :panels="expertPanels" :disabled="saving" />
                <div v-for="(messages, field) in membershipErrors" :key="field" class="text-danger" role="alert">
                    <div v-for="message in messages" :key="message">{{ message }}</div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        {{ editing.id ? 'Save Changes' : 'Create User' }}
                    </b-button>
                    <b-button variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <b-table :items="users" :fields="fields" :busy="loading" responsive striped>
            <template #table-busy><div class="text-center my-3">Loading users...</div></template>
            <template #cell(roles)="{ item }">{{ names(item.roles) }}</template>
            <template #cell(status)="{ item }">
                <b-badge :variant="item.deactivated_at ? 'secondary' : 'success'">
                    {{ item.deactivated_at ? 'Deactivated' : 'Active' }}
                </b-badge>
            </template>
            <template #cell(expert_panels_count)="{ item }">{{ item.expert_panels_count || 0 }}</template>
            <template #cell(actions)="{ item }">
                <div class="d-flex flex-wrap gap-2">
                    <b-button v-if="canViewRevisions" size="sm" variant="outline-primary" @click="revisionRecord = item">Revisions</b-button>
                    <b-button v-if="canUpdate" size="sm" variant="outline-primary" @click="startEdit(item)">Edit</b-button>
                    <b-button
                        v-if="canDeactivate && !item.deactivated_at"
                        size="sm"
                        variant="outline-danger"
                        @click="changeAccountState(item, 'deactivate')"
                    >Deactivate</b-button>
                    <b-button
                        v-if="canDeactivate && item.deactivated_at"
                        size="sm"
                        variant="outline-success"
                        @click="changeAccountState(item, 'reactivate')"
                    >Reactivate</b-button>
                </div>
            </template>
        </b-table>
        <b-pagination v-if="totalRows > perPage" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" />
    </div>
</template>

<script setup>
import AdminSearch from './AdminSearch.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useStore } from 'vuex'
import RevisionHistory from './RevisionHistory.vue'
import ExpertPanelMemberships from './ExpertPanelMemberships.vue'

const store = useStore()
const currentUser = computed(() => store.getters.getUser)
const canUpdate = computed(() => currentUser.value.hasPermission('update users'))
const canCreate = computed(() => currentUser.value.hasPermission('create users'))
const canDeactivate = computed(() => currentUser.value.hasPermission('deactivate users'))
const canViewRevisions = computed(() => currentUser.value.hasPermission('list users'))
const revisionRecord = ref(null)
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'roles', label: 'Roles' },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'expert_panels_count', label: 'Expert Panels', sortable: true },
    ]
    if (canViewRevisions.value || canUpdate.value || canDeactivate.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const users = ref([])
const currentPage = ref(1)
const perPage = 25
const totalRows = ref(0)
const roles = ref([])
const permissions = ref([])
const expertPanels = ref([])
const search = ref('')
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ name: '', email: '', role_ids: [], permission_ids: [], expert_panels: [] })
const validationErrors = ref({})
const membershipErrors = computed(() => Object.fromEntries(Object.entries(validationErrors.value).filter(([field]) => field.startsWith('expert_panels'))))
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })
const roleOptions = computed(() => roles.value.map(role => ({ value: role.id, text: role.name })))
const permissionOptions = computed(() => permissions.value.map(permission => ({ value: permission.id, text: permission.name })))

function names(items) { return items?.map(item => item.name).join(', ') || '—' }
function fieldState(field) { return validationErrors.value[field]?.length ? false : null }
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startCreate() {
    clearMessages()
    editing.value = {}
    Object.assign(form, { name: '', email: '', role_ids: [], permission_ids: [], expert_panels: [] })
}
function startEdit(user) {
    clearMessages()
    editing.value = user
    Object.assign(form, {
        name: user.name,
        email: user.email,
        role_ids: user.roles?.map(role => role.id) || [],
        permission_ids: user.permissions?.map(permission => permission.id) || [],
        expert_panels: (user.expert_panels || []).map(panel => ({
            key: panel.id, panel: { id: panel.id, name: panel.name },
            is_curator: Boolean(Number(panel.pivot.is_curator)),
            is_coordinator: Boolean(Number(panel.pivot.is_coordinator)),
            can_edit_curations: Boolean(Number(panel.pivot.can_edit_curations)),
        })),
    })
}
function cancelEdit() { editing.value = null; validationErrors.value = {} }

async function loadData() {
    loading.value = true
    try {
        const [usersResponse, optionsResponse] = await Promise.all([
            window.axios.get('/api/admin/users', { params: { page: currentPage.value, per_page: perPage, ...(search.value ? { search: search.value } : {}) } }),
            window.axios.get('/api/admin/users/options'),
        ])
        users.value = usersResponse.data.data
        totalRows.value = usersResponse.data.total
        roles.value = optionsResponse.data.roles
        permissions.value = optionsResponse.data.permissions
        expertPanels.value = optionsResponse.data.expert_panels || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load users.'
    } finally {
        loading.value = false
    }
}

async function save() {
    clearMessages()
    saving.value = true
    const payload = { name: form.name, email: form.email, role_ids: form.role_ids, permission_ids: form.permission_ids }
    payload.expert_panels = form.expert_panels.map(row => ({
        id: row.panel?.id ?? null, is_curator: row.is_curator,
        is_coordinator: row.is_coordinator, can_edit_curations: row.can_edit_curations,
    }))
    try {
        const creating = !editing.value.id
        const response = creating
            ? await window.axios.post('/api/admin/users', payload)
            : await window.axios.put(`/api/admin/users/${editing.value.id}`, payload)
        const index = users.value.findIndex(user => user.id === response.data.id)
        if (index === -1) users.value.push(response.data)
        else users.value[index] = response.data
        users.value.sort((left, right) => left.name.localeCompare(right.name))
        successMessage.value = creating ? 'User created successfully.' : 'User updated successfully.'
        editing.value = null
    } catch (error) {
        if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
        else errorMessage.value = error.response?.data?.message || 'Unable to save the user.'
    } finally {
        saving.value = false
    }
}

async function changeAccountState(user, action) {
    if (!window.confirm(`${action === 'deactivate' ? 'Deactivate' : 'Reactivate'} ${user.name}?`)) return
    clearMessages()
    try {
        const response = await window.axios.patch(`/api/admin/users/${user.id}/${action}`)
        const index = users.value.findIndex(item => item.id === response.data.id)
        if (index !== -1) users.value[index] = response.data
        successMessage.value = `User ${action}d successfully.`
    } catch (error) {
        errorMessage.value = error.response?.data?.message || `Unable to ${action} the user.`
    }
}

watch(currentPage, loadData)
function applySearch(value) {
    search.value = value
    if (currentPage.value !== 1) currentPage.value = 1
    else loadData()
}

onMounted(loadData)
</script>
