<style></style>
<template>
    <div class="card">
        <div class="card-header">
            <router-link
                id="new-topic-btn" 
                class="btn btn-secondary float-right btn-sm" 
                to="/topics/create"
            >
                Add new Topic
            </router-link>
 
            <h3>Topics in curation</h3>
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
                filter: null,
                pageLength: 8,
                currentPage: 1,
                totalRows: null,
                fields: {
                    gene_symbol: {
                        label: 'Gene Symbol',
                        sortable: true
                    },
                    expert_panel: {
                        label: 'Expert Panel',
                        sortable: true,
                    },
                    curator: {
                        label: 'Curator',
                        sortable: true,
                    },
                    current_status: {
                        label: 'Status',
                        sortable: true,
                    },
                    actions: {
                        label: 'Actions',
                        sortable: false,
                    }
                },
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