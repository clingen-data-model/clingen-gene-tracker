<style></style>

<template>
    <div class="card">
        <div class="card-header">
            <router-link
                id="new-curation-btn" 
                class="btn btn-secondary float-right btn-sm" 
                to="/curations/create"
                v-if="user.canAddCurations()"
            >
                Add new Curation
            </router-link>
 
            <h3>Dashboard: Your Curations</h3>
        </div>
        <div class="card-body">
            <curations-table :search-params="{user_id: user.user.id}"></curations-table>
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationsTable from './Curations/Table'
    
    export default {
        components: {
            CurationsTable
        },
        computed: {
            ...mapGetters({user: 'getUser'})
        }
    }
</script>
