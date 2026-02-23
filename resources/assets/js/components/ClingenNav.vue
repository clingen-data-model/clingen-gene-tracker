<style></style>
<template>
    <div class="nav-container">
        <nav class="navbar navbar-dark bg-dark navbar-expand-md sticky-top">
            <div class="container">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#clingen-nav-collapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="/">ClinGen Curation Tracker</a>
                <div class="collapse navbar-collapse" id="clingen-nav-collapse">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item"><a class="nav-link" href="#">Genes</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">GCEPS</a></li>
                    </ul>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">User</a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="/admin">Admin</a>
                                <a class="dropdown-item" href="/admin/logs">Logs</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/#logout" @click="logout()">Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</template>
<script>
    export default {
        methods: {
            logout() {
                window.axios.post('/logout')
                    .then(() => window.location.reload())
                    .catch(() => console.error('logout error'));
            }
        }
    }
</script>
