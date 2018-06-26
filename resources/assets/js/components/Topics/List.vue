<style></style>
<template>
    <div class="card">
        <div class="card-header"> 
            <h3>All Curations</h3>
        </div>
            
        <div class="card-body">
            <topics-table :topics="topics"></topics-table>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import TopicsTable from './Table'

    export default {
        components: {
            TopicsTable
        },
        data: function () {
            return {
            }
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items'
            }),
        },
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            }),
        },
        mounted: function () {
            if (this.topics.length == 0) {
                this.getAllTopics();
            }
        }
    }
</script>