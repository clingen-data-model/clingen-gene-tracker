<style></style>
<template>
    <div class="clingen-app-container container">
        <router-view></router-view>
    </div>
</template>
<script>
    import { mapActions } from 'vuex'

    export default {
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            }),
            ...mapActions('topicStatuses', {
                getAllTopicStatuses: 'getAllItems'
            }),
            ...mapActions('rationales', {
                getAllRationales: 'getAllItems'
            })
        },
        mounted: function () {
            if (this.$store.state.topics.items.length == 0) {
                this.getAllTopics();
                this.getAllTopicStatuses();
                this.getAllRationales();
            }
        }
    }
</script>