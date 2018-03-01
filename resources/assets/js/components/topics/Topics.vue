<style></style>
<template>
    <div id="topics-container">
        <b-card>
            <template slot="header">
                <router-link
                    id="new-topic-btn" 
                    class="btn btn-secondary float-right btn-sm" 
                    to="/topics/create"
                >
                    Add new Topic
                </router-link>
                 
                <h3>Topics in curation</h3>
            </template>
            <topics-table></topics-table>
        </b-card>
    </div>
</template>
<script>
    export default {
        components: {
            'topics-table': require('./TopicsTable.vue'),
        },
    }
</script>