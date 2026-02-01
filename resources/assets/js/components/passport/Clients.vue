<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <div class="card card-default">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>
                        OAuth Clients
                    </span>

                    <a class="action-link" tabindex="-1" @click="showCreateClientForm">
                        Create New Client
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Current Clients -->
                <p class="mb-0" v-if="clients.length === 0">
                    You have not created any OAuth clients.
                </p>

                <table class="table table-borderless mb-0" v-if="clients.length > 0">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Secret</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="client in clients" :key="client.id">
                            <!-- ID -->
                            <td style="vertical-align: middle;">
                                {{ client.id }}
                            </td>

                            <!-- Name -->
                            <td style="vertical-align: middle;">
                                {{ client.name }}
                            </td>

                            <!-- Secret -->
                            <td style="vertical-align: middle;">
                                <code>{{ client.secret }}</code>
                            </td>

                            <!-- Edit Button -->
                            <td style="vertical-align: middle;">
                                <a class="action-link" tabindex="-1" @click="edit(client)">
                                    Edit
                                </a>
                            </td>

                            <!-- Delete Button -->
                            <td style="vertical-align: middle;">
                                <a class="action-link text-danger" @click="destroy(client)">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Client Modal -->
        <Dialog v-model:visible="showCreateModal" header="Create Client" :modal="true" :style="{ width: '500px' }">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="createForm.errors.length > 0">
                <p class="mb-0"><strong>Whoops!</strong> Something went wrong!</p>
                <br>
                <ul>
                    <li v-for="(error, idx) in createForm.errors" :key="idx">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <!-- Create Client Form -->
            <form role="form">
                <!-- Name -->
                <div class="mb-3 row">
                    <label class="col-md-3 col-form-label">Name</label>

                    <div class="col-md-9">
                        <input id="create-client-name" type="text" class="form-control"
                                                    @keyup.enter="store" v-model="createForm.name" ref="createNameInput">

                        <span class="form-text text-muted">
                            Something your users will recognize and trust.
                        </span>
                    </div>
                </div>

                <!-- Redirect URL -->
                <div class="mb-3 row">
                    <label class="col-md-3 col-form-label">Redirect URL</label>

                    <div class="col-md-9">
                        <input type="text" class="form-control" name="redirect"
                                        @keyup.enter="store" v-model="createForm.redirect">

                        <span class="form-text text-muted">
                            Your application's authorization callback URL.
                        </span>
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

        <!-- Edit Client Modal -->
        <Dialog v-model:visible="showEditModal" header="Edit Client" :modal="true" :style="{ width: '500px' }">
            <!-- Form Errors -->
            <div class="alert alert-danger" v-if="editForm.errors.length > 0">
                <p class="mb-0"><strong>Whoops!</strong> Something went wrong!</p>
                <br>
                <ul>
                    <li v-for="(error, idx) in editForm.errors" :key="idx">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <!-- Edit Client Form -->
            <form role="form">
                <!-- Name -->
                <div class="mb-3 row">
                    <label class="col-md-3 col-form-label">Name</label>

                    <div class="col-md-9">
                        <input id="edit-client-name" type="text" class="form-control"
                                                    @keyup.enter="update" v-model="editForm.name" ref="editNameInput">

                        <span class="form-text text-muted">
                            Something your users will recognize and trust.
                        </span>
                    </div>
                </div>

                <!-- Redirect URL -->
                <div class="mb-3 row">
                    <label class="col-md-3 col-form-label">Redirect URL</label>

                    <div class="col-md-9">
                        <input type="text" class="form-control" name="redirect"
                                        @keyup.enter="update" v-model="editForm.redirect">

                        <span class="form-text text-muted">
                            Your application's authorization callback URL.
                        </span>
                    </div>
                </div>
            </form>

            <!-- Modal Actions -->
            <template #footer>
                <button type="button" class="btn btn-secondary" @click="showEditModal = false">Close</button>

                <button type="button" class="btn btn-primary" @click="update">
                    Save Changes
                </button>
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
                clients: [],
                showCreateModal: false,
                showEditModal: false,

                createForm: {
                    errors: [],
                    name: '',
                    redirect: ''
                },

                editForm: {
                    errors: [],
                    name: '',
                    redirect: ''
                }
            };
        },

        mounted() {
            this.getClients();
        },

        methods: {
            getClients() {
                axios.get('/oauth/clients')
                        .then(response => {
                            this.clients = response.data;
                        });
            },

            showCreateClientForm() {
                this.showCreateModal = true;
                this.$nextTick(() => {
                    this.$refs.createNameInput?.focus();
                });
            },

            store() {
                this.persistClient(
                    'post', '/oauth/clients',
                    this.createForm, 'showCreateModal'
                );
            },

            edit(client) {
                this.editForm.id = client.id;
                this.editForm.name = client.name;
                this.editForm.redirect = client.redirect;

                this.showEditModal = true;
                this.$nextTick(() => {
                    this.$refs.editNameInput?.focus();
                });
            },

            update() {
                this.persistClient(
                    'put', '/oauth/clients/' + this.editForm.id,
                    this.editForm, 'showEditModal'
                );
            },

            persistClient(method, uri, form, modalKey) {
                form.errors = [];

                axios[method](uri, form)
                    .then(response => {
                        this.getClients();

                        form.name = '';
                        form.redirect = '';
                        form.errors = [];

                        this[modalKey] = false;
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = Object.values(error.response.data.errors).flat();
                        } else {
                            form.errors = ['Something went wrong. Please try again.'];
                        }
                    });
            },

            destroy(client) {
                axios.delete('/oauth/clients/' + client.id)
                        .then(response => {
                            this.getClients();
                        });
            }
        }
    }
</script>
