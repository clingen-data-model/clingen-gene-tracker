<style></style>
<template>
    <div class="topic-show-container">
        <div>
            <router-link to="/topics">
                    &lt; Back to topics
            </router-link>
        
        </div>
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
                <div class="row mt-1">
                    <strong class="col-md-2">Gene Symbol:</strong> 
                    <div class="col-md">{{ topic.gene_symbol }}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Expert Panel:</strong> 
                    <div class="col-md">{{ (topic.expert_panel) ? topic.expert_panel.name : '--'}}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Curator:</strong> 
                    <div class="col-md">{{ (topic.curator) ? topic.curator.name : '--'}}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Curation Type:</strong>
                    <div class="col-md">
                        {{topic.curation_type ? topic.curation_type.description : '--'}}
                    </div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Phenotypes:</strong>
                    <phenotype-list :topic="topic" :gene-symbol="topic.gene_symbol" class="col-md"></phenotype-list>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Rationale:</strong>
                    <div class="col-md">
                        {{
                            (topic.rationale) 
                                ? topic.rationale.name + ((topic.rationale_other) ? ' - '+topic.rationale_other : '')
                                : '--'
                        }} 
                    </div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">PMIDS</strong>
                    <div class="col-md" v-if="topic.pmids">
                        <ul class="list-inline">
                            <li v-for="(pmid, idx) in topic.pmids" class="list-inline-item">
                                {{pmid}}<span v-if="topic.pmids && topic.pmids.length > idx+1">,</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Notes on Rationale</strong>
                    <div class="col-md">
                        {{topic.rationale_notes}}
                    </div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">MonDO ID:</strong> 
                    <div class="col-md">{{ (topic.mondo_id) ? topic.mondo_id : '--'}}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Status:</strong>
                    <div class="col-md">{{ (topic.topic_status) ? topic.topic_status.name : '--'}}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Notes:</strong> 
                    <div class="col-md">{{ (topic.notes) ? topic.notes : '--' }}</div>
                </div>
                <div class="row mt-1">
                    <strong class="col-md-2">Disease entity notes:</strong> 
                    <div class="col-md">{{ (topic.disease_entity_notes) ? topic.disease_entity_notes : '--' }}</div>
                </div>
            </div>
        </b-card>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import PhenotypeList from './Phenotypes/List'

    export default {
        props: ['id'],
        components: {
            'phenotype-list': PhenotypeList
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items',
                getTopic: 'getItemById'
            }),            
            title: function () {
                let title = 'Topic: ';
                if (this.topic.gene_symbol) {
                    title += this.topic.gene_symbol
                    if (this.topic.mondo_id) {
                        title += ' / ' + this.topic.mondo_id
                    }
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

                return this.getTopic(this.id);
            }

        },
        methods: {
            ...mapActions('topics', {
                fetchTopic: 'fetchItem'
            })
        },
        mounted: function () {
            this.fetchTopic(this.id);
        }
    }
</script>