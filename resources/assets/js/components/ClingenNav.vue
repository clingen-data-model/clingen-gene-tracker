<style></style>
<template>
    <div class="nav-container">
        <b-navbar toggleable="md" type="dark" variant="dark" sticky>
            <div class="container">
                <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>
                <b-navbar-brand href="/">ClinGen Curation Tracker</b-navbar-brand>
                <b-collapse is-nav id="nav-collapse">
                    <b-navbar-nav>
                        <b-nav-item href="#">Genes</b-nav-item>
                        <b-nav-item href="#">GCEPS</b-nav-item>
                    </b-navbar-nav>

                    <b-navbar-nav class="ml-auto">
                        <b-nav-item-dropdown text="User" right>
                            <b-dropdown-item href="/admin">Admin</b-dropdown-item>
                            <b-dropdown-item href="/logs" v-show="true">Logs</b-dropdown-item>
                            <b-dropdown-divider></b-dropdown-divider>
                            <b-dropdown-item href="/#logout" @click="logout()">Logout</b-dropdown-item>
                        </b-nav-item-dropdown>
                    </b-navbar-nav>
                </b-collapse>
            </div>
        </b-navbar>
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