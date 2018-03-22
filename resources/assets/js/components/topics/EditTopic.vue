<style></style>
<template>
    <div>
        <p>
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
                     <button
                        :id="'edit-topic-'+topic.id+'-btn'" 
                        class="btn btn-secondary float-right btn-sm" 
                        @click="$router.go(-1)"
                    >
                        cancel
                    </button>
                </h3>
            </template>
            <div v-if="this.topics">
                <topic-form :topic="topic" @canceled="$router.go(-1)">         
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
            })
        },
        mounted: function () {
            if (this.topics.length == 0) {
                this.getAllTopics();
            }
        }
    }
</script>