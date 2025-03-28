<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

const user = computed(() => {
    return store.getters.getUser
})

</script>

<template>
    <nav class="navbar navbar-default navbar-expand-md navbar-light navbar-laravel {{ config('app.env') }}">
        <div class="container">
            <a class="navbar-brand" href="/#/">
                <img src="/images/clingen_logo_75w.png">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto" v-if="user">
                    <li v-if="user.canAddCurations()">
                        <RouterLink class="nav-link" to="/">Dashboard</RouterLink>
                    </li>
                    <li>
                        <RouterLink class="nav-link" to="/curations">Curations</RouterLink>
                    </li>
                    <li>
                        <RouterLink class="nav-link" to="/working-groups">Working Groups</RouterLink>
                    </li>
                    <li>
                        <RouterLink class="nav-link" to="/curations/export">Curation Export</RouterLink>
                    </li>
                    <li class="nav-item dropdown">
                        <b-dropdown variant="outline" class="nav-item" text="Bulk Lookup">
                            <b-dropdown-item>
                                <RouterLink class="dropdown-item" to="/bulk-lookup/curations">Curation Lookup</RouterLink>
                            </b-dropdown-item>
                            <b-dropdown-item>
                                <RouterLink class="dropdown-item" to="/bulk-lookup/genes">Gene/Phenotype Lookup</RouterLink>
                            </b-dropdown-item>
                        </b-dropdown>
                    </li>
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    <li v-if="user" class="nav-item dropdown">
                        <b-dropdown variant="outline" :text="user.user.name">
                            <b-dropdown-item>
                                <RouterLink class="dropdown-item" to="/curations/export">
                                    Curation Export
                                </RouterLink>
                            </b-dropdown-item>
                            <b-dropdown-item v-if="user.hasAnyRole('programmer', 'admin', 'coordinator')" class="dropdown-item"
                                href="/bulk-uploads">Bulk Upload</b-dropdown-item>

                            <b-dropdown-item v-if="user.hasAnyRole('programmer', 'admin')" href="/admin" class="dropdown-item">Admin</b-dropdown-item>
                            <b-dropdown-item v-if="user.hasRole('programmer')" class="dropdown-item" href="/admin/logs" target="logs">Logs</b-dropdown-item>

                            <b-dropdown-item class="dropdown-item" href="/files/SOP_V1.pdf" target="sop">SOP</b-dropdown-item>

                            <b-dropdown-divider/>

                            <a class="dropdown-item" href="/logout" onclick="event.preventDefault();
                                                document.getElementById('logout-form').submit();">
                                Logout
                            </a>

                            <form id="logout-form" action="/logout" method="POST" style="display: none;">
                                <!-- @csrf -->
                            </form>
                        </b-dropdown>
                    </li>
                    <li v-else><a class="nav-link" href="/login">Login</a></li>
                    <li class="nav-item" style="margin-right: -46px">
                        <a class="nav-link btn-help" href="#get-help" title="Get help or report a problem"
                            data-toggle="modal" data-target="#help-modal">?</a>
                    </li>

                    <div class="modal fade" tabindex="-1" role="dialog" id="help-modal">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">How can we help?</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="lead text-center">
                                        If you have any questions, please contact our help desk at <a
                                            href="mailto:clingentrackerhelp@unc.edu">clingentrackerhelp@unc.edu</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>
        </div>
    </nav>
</template>