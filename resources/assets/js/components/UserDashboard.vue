<style></style>

<template>
    <div class="card">
        <div class="card-header">
            <router-link
                id="new-topic-btn" 
                class="btn btn-secondary float-right btn-sm" 
                to="/topics/create"
                v-if="user.canAddTopics()"
            >
                Add new Topic
            </router-link>
 
            <h3>Your Topics</h3>
        </div>
        
        <topics-table :topics="userTopics"></topics-table>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import TopicsTable from './Topics/Table'
    
    export default {
        components: {
            TopicsTable
        },
        data() {
            return {
                user: user
            }
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items'
            }),
            userTopics: function() {
                let userTopics = [];
                if (this.topics.length > 0) {
                    userTopics = this.topics.filter(topic => {
                        const canEdit = user.canEditTopic(topic);
                        return canEdit;
                    })
                }
                return userTopics
            }
        },
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            }),
        },
        mounted: function () {
            if (this.topics.length == 0) {
                console.log('no topics in memory.  get them')
                this.getAllTopics();
            }
        }
    }
</script>
