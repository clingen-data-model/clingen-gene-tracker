<style></style>
<template>
    <div>
        <p>
            <pre v-if="lastRoute != null">{{lastRoute.path}}</pre>
            <router-link to="/topics">
                    &lt; Back to topics
            </router-link>
        </p>
        <b-card
            id="edit-topic-modal"
        >
            <template slot="header">
                <h3>
                    {{ title }}
                </h3>
            </template>
            <div v-if="this.topics">
                <topic-form :topic="topic" @canceled="goToLastRoute()">         
                </topic-form>
            </div>
        </b-card>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import TopicForm from './Form'

    export default {
        props: ['id'],
        components: {
            topicForm: TopicForm
        },
        data () {
            return {
                lastRoute: null,
            }
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items',
                getTopic: 'getItemById'
            }),            
            title: function () {
                let title = 'Edit Topic: ';
                if (this.topic.gene_symbol) {
                    title += this.topic.gene_symbol
                    if (this.topic.expert_panel) {
                        title += ' for '+this.topic.expert_panel.name
                    }
                }
                return title;
            },
            topic: function(){
                if (this.topics.length == 0) {
                    return {
                        expert_panel: {}
                    }
                }

                const topic = this.getTopic(this.id);
                return topic;
            },
            curator: function () {
                return (this.topic.curator) ? this.topic.curator.name : '--';
            },
            expertPanel: () => { return (this.expert_panel) ? this.topic.expert_panel.name : '--'; }

        },
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            }),
            goToLastRoute() {
                this.$router.go('-1')
                // this.$router.push(this.lastRoute)
            }
        },
        mounted: function () {
            if (this.topics.length == 0) {
                this.getAllTopics();
            }
        }
    }
</script>