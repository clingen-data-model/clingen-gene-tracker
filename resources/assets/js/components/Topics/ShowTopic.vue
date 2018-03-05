<style></style>
<template>
    <b-card
        id="show-topic"
    >
        <template slot="header">
            <h3>{{ title }}
             <router-link
                :id="'edit-topic-'+topic.id+'-btn'" 
                class="btn btn-secondary float-right btn-sm" 
                :to="'/topics/'+topic.id+'/edit'"
            >
                Edit
            </router-link>
            </h3>
       </template>
        <div v-if="this.topics">
            <p><strong>Gene Symbol</strong>: {{ topic.gene_symbol }}</p>
            <p><strong>Expert Panel</strong>: {{ (topic.expert_panel) ? topic.expert_panel.name : '--'}}</p>
            <p><strong>Curator</strong>: {{ (topic.curator) ? topic.curator.name : '--'}}</p>
        </div>
    </b-card>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {
        props: ['id'],
        computed: {
            ...mapGetters('topics', {
                topics: 'Items'
            }),            
            title: function () {
                let title = 'Topic: ';
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
                    console.log("no topics");
                    return {}
                }

                return this.topics.find( function (element) {
                    return element.id == this.id
                }.bind(this))
            }

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