<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>User Administration</h2>
        </div>

        <p class="text-muted">
            Expert Panel and affiliation memberships are shown as counts and are managed separately.
        </p>
        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" title="Edit User">
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
                    <b-form-select id="user-permissions" v-model="form.permission_ids" :options="permissionOptions" multiple :disabled="saving" />
                    <div class="form-text">These permissions are assigned directly in addition to permissions inherited from roles.</div>
                </b-form-group>
                <div class="mt-3 d-flex gap-2">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        Save Changes
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
                <div class="d-flex gap-2">
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
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const currentUser = computed(() => store.getters.getUser)
const canUpdate = computed(() => currentUser.value.hasPermission('update users'))
const canDeactivate = computed(() => currentUser.value.hasPermission('deactivate users'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'roles', label: 'Roles' },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'expert_panels_count', label: 'Expert Panels', sortable: true },
    ]
    if (canUpdate.value || canDeactivate.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const users = ref([])
const roles = ref([])
const permissions = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ name: '', email: '', role_ids: [], permission_ids: [] })
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const showSuccess = computed({ get: () => Boolean(successMessage.value), set: value => { if (!value) successMessage.value = '' } })
const showError = computed({ get: () => Boolean(errorMessage.value), set: value => { if (!value) errorMessage.value = '' } })
const roleOptions = computed(() => roles.value.map(role => ({ value: role.id, text: role.name })))
const permissionOptions = computed(() => permissions.value.map(permission => ({ value: permission.id, text: permission.name })))

function names(items) { return items?.map(item => item.name).join(', ') || '—' }
function fieldState(field) { return validationErrors.value[field]?.length ? false : null }
function clearMessages() { validationErrors.value = {}; successMessage.value = ''; errorMessage.value = '' }
function startEdit(user) {
    clearMessages()
    editing.value = user
    Object.assign(form, {
        name: user.name,
        email: user.email,
        role_ids: user.roles?.map(role => role.id) || [],
        permission_ids: user.permissions?.map(permission => permission.id) || [],
    })
}
function cancelEdit() { editing.value = null; validationErrors.value = {} }

async function loadData() {
    loading.value = true
    try {
        const [usersResponse, optionsResponse] = await Promise.all([
            window.axios.get('/api/admin/users'),
            window.axios.get('/api/admin/users/options'),
        ])
        users.value = usersResponse.data
        roles.value = optionsResponse.data.roles
        permissions.value = optionsResponse.data.permissions
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
    try {
        const response = await window.axios.put(`/api/admin/users/${editing.value.id}`, payload)
        const index = users.value.findIndex(user => user.id === response.data.id)
        if (index === -1) users.value.push(response.data)
        else users.value[index] = response.data
        users.value.sort((left, right) => left.name.localeCompare(right.name))
        successMessage.value = 'User updated successfully.'
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

onMounted(loadData)
</script>
