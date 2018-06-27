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
 
            <h3>Your Curations</h3>
        </div>
        
        <curations-table :curations="userCurations"></curations-table>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationsTable from './Curations/Table'
    
    export default {
        components: {
            CurationsTable
        },
        data() {
            return {
                user: user
            }
        },
        computed: {
            ...mapGetters('curations', {
                curations: 'Items'
            }),
            userCurations: function() {
                let userCurations = [];
                if (this.curations.length > 0) {
                    userCurations = this.curations.filter(curation => {
                        const canEdit = user.canEditCuration(curation);
                        return canEdit;
                    })
                }
                return userCurations
            }
        },
        methods: {
            ...mapActions('curations', {
                getAllCurations: 'getAllItems'
            }),
        },
        mounted: function () {
            if (this.curations.length == 0) {
                console.log('no curations in memory.  get them')
                this.getAllCurations();
            }
        }
    }
</script>
