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
                            <tr v-for="token in tokens" :key="token.id">
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
        <Dialog v-model:visible="showCreateModal" header="Create Token" :modal="true" :style="{ width: '500px' }">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="form.errors.length > 0">
                <p class="mb-0"><strong>Whoops!</strong> Something went wrong!</p>
                <br>
                <ul>
                    <li v-for="(error, idx) in form.errors" :key="idx">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <!-- Create Token Form -->
            <form role="form" @submit.prevent="store">
                <!-- Name -->
                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label">Name</label>

                    <div class="col-md-6">
                        <input id="create-token-name" type="text" class="form-control" name="name" v-model="form.name" ref="tokenNameInput">
                    </div>
                </div>

                <!-- Scopes -->
                <div class="mb-3" v-if="scopes.length > 0">
                    <label class="col-md-4 col-form-label">Scopes</label>

                    <div class="col-md-6">
                        <div v-for="scope in scopes" :key="scope.id">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    @click="toggleScope(scope.id)"
                                    :checked="scopeIsAssigned(scope.id)"
                                    :id="'scope-' + scope.id">
                                <label class="form-check-label" :for="'scope-' + scope.id">
                                    {{ scope.id }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Modal Actions -->
            <template #footer>
                <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Close</button>

                <button type="button" class="btn btn-primary" @click="store">
                    Create
                </button>
            </template>
        </Dialog>

        <!-- Access Token Modal -->
        <Dialog v-model:visible="showAccessTokenModal" header="Personal Access Token" :modal="true" :style="{ width: '500px' }">
            <p>
                Here is your new personal access token. This is the only time it will be shown so don't lose it!
                You may now use this token to make API requests.
            </p>

            <textarea class="form-control" rows="10">{{ accessToken }}</textarea>

            <!-- Modal Actions -->
            <template #footer>
                <button type="button" class="btn btn-secondary" @click="showAccessTokenModal = false">Close</button>
            </template>
        </Dialog>
    </div>
</template>

<script>
    import Dialog from 'primevue/dialog'

    export default {
        components: {
            Dialog,
        },
        data() {
            return {
                accessToken: null,

                tokens: [],
                scopes: [],

                showCreateModal: false,
                showAccessTokenModal: false,

                form: {
                    name: '',
                    scopes: [],
                    errors: []
                }
            };
        },

        mounted() {
            this.getTokens();
            this.getScopes();
        },

        methods: {
            getTokens() {
                axios.get('/oauth/personal-access-tokens')
                        .then(response => {
                            this.tokens = response.data;
                        });
            },

            getScopes() {
                axios.get('/oauth/scopes')
                        .then(response => {
                            this.scopes = response.data;
                        });
            },

            showCreateTokenForm() {
                this.showCreateModal = true;
                this.$nextTick(() => {
                    this.$refs.tokenNameInput?.focus();
                });
            },

            store() {
                this.accessToken = null;

                this.form.errors = [];

                axios.post('/oauth/personal-access-tokens', this.form)
                        .then(response => {
                            this.form.name = '';
                            this.form.scopes = [];
                            this.form.errors = [];

                            this.tokens.push(response.data.token);

                            this.showAccessToken(response.data.accessToken);
                        })
                        .catch(error => {
                            if (typeof error.response.data === 'object') {
                                this.form.errors = Object.values(error.response.data.errors).flat();
                            } else {
                                this.form.errors = ['Something went wrong. Please try again.'];
                            }
                        });
            },

            toggleScope(scope) {
                if (this.scopeIsAssigned(scope)) {
                    this.form.scopes = this.form.scopes.filter(s => s !== scope);
                } else {
                    this.form.scopes.push(scope);
                }
            },

            scopeIsAssigned(scope) {
                return this.form.scopes.indexOf(scope) >= 0;
            },

            showAccessToken(accessToken) {
                this.showCreateModal = false;

                this.accessToken = accessToken;

                this.showAccessTokenModal = true;
            },

            revoke(token) {
                axios.delete('/oauth/personal-access-tokens/' + token.id)
                        .then(response => {
                            this.getTokens();
                        });
            }
        }
    }
</script>
