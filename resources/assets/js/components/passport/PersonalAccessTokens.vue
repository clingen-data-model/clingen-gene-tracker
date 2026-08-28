<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <div>
            <div class="card card-default">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            Personal Access Tokens
                        </span>

                        <a class="action-link" tabindex="-1" @click="showCreateTokenForm">
                            Create New Token
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- No Tokens Notice -->
                    <p class="mb-0" v-if="tokens.length === 0">
                        You have not created any personal access tokens.
                    </p>

                    <!-- Personal Access Tokens -->
                    <table class="table table-borderless mb-0" v-if="tokens.length > 0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="token in tokens">
                                <!-- Client Name -->
                                <td style="vertical-align: middle;">
                                    {{ token.name }}
                                </td>

                                <!-- Delete Button -->
                                <td style="vertical-align: middle;">
                                    <a class="action-link text-danger" @click="revoke(token)">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Token Modal -->
        <b-modal v-model="showCreateModal" title="Create Token" @shown="focusTokenName">
                        <!-- Form Errors -->
                        <div class="alert alert-danger" v-if="form.errors.length > 0">
                            <p class="mb-0"><strong>Whoops!</strong> Something went wrong!</p>
                            <br>
                            <ul>
                                <li v-for="error in form.errors">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <!-- Create Token Form -->
                        <form role="form" @submit.prevent="store">
                            <!-- Name -->
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label">Name</label>

                                <div class="col-md-6">
                                    <input ref="tokenName" id="create-token-name" type="text" class="form-control" name="name" v-model="form.name">
                                </div>
                            </div>

                            <!-- Scopes -->
                            <div class="form-group" v-if="scopes.length > 0">
                                <label class="col-md-4 col-form-label">Scopes</label>

                                <div class="col-md-6">
                                    <div v-for="scope in scopes">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    @click="toggleScope(scope.id)"
                                                    :checked="scopeIsAssigned(scope.id)">

                                                    {{ scope.id }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
            <template #modal-footer>
                        <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Close</button>

                        <button type="button" class="btn btn-primary" @click="store">
                            Create
                        </button>
            </template>
        </b-modal>

        <!-- Access Token Modal -->
        <b-modal v-model="showAccessModal" title="Personal Access Token">
                        <p>
                            Here is your new personal access token. This is the only time it will be shown so don't lose it!
                            You may now use this token to make API requests.
                        </p>

                        <textarea class="form-control" rows="10">{{ accessToken }}</textarea>
            <template #modal-footer>
                <button type="button" class="btn btn-secondary" @click="showAccessModal = false">Close</button>
            </template>
        </b-modal>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'

const accessToken = ref(null)
const showCreateModal = ref(false)
const showAccessModal = ref(false)
const tokens = ref([])
const scopes = ref([])
const tokenName = ref(null)
const form = reactive({
    name: '',
    scopes: [],
    errors: []
})

function focusTokenName() {
    tokenName.value.focus()
}

function getTokens() {
    axios.get('/oauth/personal-access-tokens')
        .then(response => {
            tokens.value = response.data
        })
}

function getScopes() {
    axios.get('/oauth/scopes')
        .then(response => {
            scopes.value = response.data
        })
}

function showCreateTokenForm() {
    showCreateModal.value = true
}

function store() {
    accessToken.value = null
    form.errors = []

    axios.post('/oauth/personal-access-tokens', form)
        .then(response => {
            form.name = ''
            form.scopes = []
            form.errors = []
            tokens.value.push(response.data.token)
            showAccessToken(response.data.accessToken)
        })
        .catch(error => {
            if (typeof error.response.data === 'object') {
                form.errors = _.flatten(_.toArray(error.response.data.errors))
            } else {
                form.errors = ['Something went wrong. Please try again.']
            }
        })
}

function toggleScope(scope) {
    if (scopeIsAssigned(scope)) {
        form.scopes = _.reject(form.scopes, item => item == scope)
    } else {
        form.scopes.push(scope)
    }
}

function scopeIsAssigned(scope) {
    return _.indexOf(form.scopes, scope) >= 0
}

function showAccessToken(value) {
    showCreateModal.value = false
    accessToken.value = value
    showAccessModal.value = true
}

function revoke(token) {
    axios.delete('/oauth/personal-access-tokens/' + token.id)
        .then(() => {
            getTokens()
        })
}

onMounted(() => {
    getTokens()
    getScopes()
})
</script>
