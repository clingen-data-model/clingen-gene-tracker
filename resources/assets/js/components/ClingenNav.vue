<style></style>
<template>
    <div class="nav-container">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav-collapse" aria-controls="nav-collapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="/">ClinGen Curation Tracker</a>
                <div class="collapse navbar-collapse" id="nav-collapse">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="#">Genes</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">GCEPS</a></li>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">User</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/admin">Admin</a></li>
                                <li><a class="dropdown-item" href="/admin/logs" v-show="true">Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/#logout" @click="logout()">Logout</a></li>
                            </ul>
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
            logout: function () {
                window.axios.post('/logout')
                    .then(function (response) {
                        window.location.reload();
                    })
                    .catch(function (error) {
                        console.error('error');
                    });
            }
        }
    }
</script>
