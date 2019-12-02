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
            <curations-table></curations-table>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationsTable from './Table'

    export default {
        components: {
            CurationsTable
        }
    }
</script>