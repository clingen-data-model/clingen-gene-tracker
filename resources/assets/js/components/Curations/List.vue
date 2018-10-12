<style></style>
<template>
    <div class="card">
        <div class="card-header"> 
            <span class="float-right">
                <router-link :to="'/curations/export'" class="btn btn-secondary btn-sm">Download CSV</router-link>
            </span>
            <h3>All Curations</h3>
        </div>
            
        <div class="card-body">
            <curations-table :curations="curations"></curations-table>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationsTable from './Table'

    export default {
        components: {
            CurationsTable
        },
        data: function () {
            return {
            }
        },
        computed: {
            ...mapGetters('curations', {
                curations: 'Items'
            }),
        },
        methods: {
            ...mapActions('curations', {
                getAllCurations: 'getAllItems'
            }),
        },
        mounted: function () {
            // if (this.curations.length == 0) {
            this.getAllCurations();
            // }
        }
    }
</script>