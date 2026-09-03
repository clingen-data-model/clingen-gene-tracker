<template>
    <div>
        <h2>Modes of Inheritance</h2>
        <p class="text-muted">Canonical HPO fields are read-only. Administration controls whether an MOI is available for curation.</p>

        <b-alert v-model="showSuccess" variant="success" dismissible>{{ successMessage }}</b-alert>
        <b-alert v-model="showError" variant="danger" dismissible>{{ errorMessage }}</b-alert>

        <b-card v-if="editing" class="mb-4" title="Edit Mode of Inheritance">
            <dl class="row mb-3">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ editing.name }}</dd>
                <dt class="col-sm-3">Abbreviation</dt><dd class="col-sm-9">{{ editing.abbreviation || '—' }}</dd>
                <dt class="col-sm-3">HP ID</dt><dd class="col-sm-9">{{ editing.hp_id }}</dd>
                <dt class="col-sm-3">Parent</dt><dd class="col-sm-9">{{ editing.parent?.name || '—' }}</dd>
            </dl>
            <b-form @submit.prevent="save">
                <b-form-group label="Curatable" label-for="moi-curatable">
                    <b-form-select
                        id="moi-curatable"
                        v-model="form.curatable"
                        :options="curatableOptions"
                        :state="fieldState('curatable')"
                        :disabled="saving"
                    />
                    <b-form-invalid-feedback v-for="message in validationErrors.curatable || []" :key="message">
                        {{ message }}
                    </b-form-invalid-feedback>
                </b-form-group>
                <div class="mt-3">
                    <b-button type="submit" variant="primary" :disabled="saving">
                        <b-spinner v-if="saving" small aria-label="Saving" />
                        Save Curatable Setting
                    </b-button>
                    <b-button class="ms-2" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</b-button>
                </div>
            </b-form>
        </b-card>

        <div v-if="loading" class="text-center py-5" role="status">
            <b-spinner label="Loading modes of inheritance" />
            <span class="visually-hidden">Loading modes of inheritance</span>
        </div>

        <b-table
            v-else
            :items="mois"
            :fields="fields"
            responsive
            striped
            hover
            show-empty
            empty-text="No modes of inheritance found."
        >
            <template #cell(parent)="{ item }">{{ item.parent?.name || '—' }}</template>
            <template #cell(curatable)="{ item }">{{ item.curatable ? 'Yes' : 'No' }}</template>
            <template #cell(actions)="{ item }">
                <b-button size="sm" variant="outline-primary" @click="startEdit(item)">Edit Curatable</b-button>
            </template>
        </b-table>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
const canUpdate = computed(() => user.value.hasPermission('update mois'))
const fields = computed(() => {
    const values = [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'abbreviation', label: 'Abbreviation', sortable: true },
        { key: 'hp_id', label: 'HP ID', sortable: true },
        { key: 'parent', label: 'Parent' },
        { key: 'curatable', label: 'Curatable', sortable: true },
    ]
    if (canUpdate.value) values.push({ key: 'actions', label: 'Actions' })
    return values
})

const mois = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(null)
const form = reactive({ curatable: false })
const validationErrors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const curatableOptions = [
    { value: true, text: 'Yes' },
    { value: false, text: 'No' },
]
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

async function loadMois() {
    loading.value = true
    try {
        const response = await window.axios.get('/api/admin/mois')
        mois.value = response.data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load modes of inheritance.'
    } finally {
        loading.value = false
    }
}

function startEdit(moi) {
    clearMessages()
    editing.value = moi
    form.curatable = Boolean(moi.curatable)
}

function cancelEdit() {
    editing.value = null
    validationErrors.value = {}
}

async function save() {
    clearMessages()
    saving.value = true
    try {
        const response = await window.axios.put(`/api/admin/mois/${editing.value.id}`, {
            curatable: form.curatable,
        })
        const index = mois.value.findIndex(item => item.id === response.data.id)
        if (index !== -1) mois.value[index] = response.data
        editing.value = null
        successMessage.value = 'Mode of inheritance updated successfully.'
    } catch (error) {
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors || {}
        } else {
            errorMessage.value = error.response?.data?.message || 'Unable to update the mode of inheritance.'
        }
    } finally {
        saving.value = false
    }
}

onMounted(loadMois)
</script>
