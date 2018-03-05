<style></style>
<template>
    <b-card
        id="edit-topic-modal"
    >
        <template slot="header">
            <h3>
                {{ title }}
                 <router-link
                    :id="'edit-topic-'+topic.id+'-btn'" 
                    class="btn btn-secondary float-right btn-sm" 
                    :to="'/topics/'+topic.id"
                >
                    cancel
                </router-link>
            </h3>
        </template>
        <div v-if="this.topics">
            <topic-form :topic="topic">
                
            </topic-form>
        </div>
    </b-card>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {
        props: ['id'],
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

                return this.getTopic(this.id);
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