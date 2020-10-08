<template>
    <div class="curation-show-container">
        <div>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>
        
        </div>
        <transition name="fade">
        <b-card
            id="show-curation"
            v-if="!loading"
            key="curation-details"
            style="max-heigh: 1000px"
        >
            <template slot="header">
                <h3>{{ title }}

                    <div class="float-right d-block" v-if="!loading">
                        <router-link
                            v-if="user.canEditCuration(curation)"
                            :id="'edit-curation-'+curation.id+'-btn'" 
                            class="btn btn-secondary btn-sm" 
                            :to="'/curations/'+curation.id+'/edit'"
                        >
                            Edit
                        </router-link>
                        <delete-button class="btn-sm" :curation="curation"></delete-button>
                    </div>                
                </h3>
           </template>
            <div v-if="this.curations">
                <div id="info">
                    <div class="row mt-2">
                        <strong class="col-md-2">Gene Symbol:</strong> 
                        <div class="col-md">{{ curation.gene_symbol }}</div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">HGNC ID:</strong> 
                        <div class="col-md">
                            <span v-if="curation.hgnc_id">{{`hgnch:${curation.hgnc_id}`}}</span>
                            <small class="text-muted" v-else>
                                HGNC info will update shortly
                            </small>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">HGNC Name:</strong> 
                        <div class="col-md">
                            <span v-if="curation.name">{{`hgnch:${curation.name}`}}</span>
                            <small class="text-muted" v-else>
                                HGNC info will update shortly
                            </small>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">Expert Panel:</strong> 
                        <div class="col-md">{{ (curation.expert_panel) ? curation.expert_panel.name : '--'}}</div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">Curator:</strong> 
                        <div class="col-md">{{ (curation.curator) ? curation.curator.name : '--'}}</div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">Curation Type:</strong>
                        <div class="col-md">
                            {{curation.curation_type ? curation.curation_type.description : '--'}}
                        </div>
                    </div>
                    <div class="row mt-4">
                        <strong class="col-md-2">Phenotypes:</strong>
                        <phenotype-list :curation="curation" :gene-symbol="curation.gene_symbol" class="col-md"></phenotype-list>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">Rationale:</strong>
                        <div class="col-md">
                            <ul class="list-inline">
                                <li v-for="(rationale, idx) in curation.rationales" :key="rationale.id" class="list-inline-item">
                                    {{rationale.name}}<span v-if="idx+1 < curation.rationales.length">,</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">PMIDS</strong>
                        <div class="col-md" v-if="curation.pmids">
                            <ul class="list-inline">
                                <li v-for="(pmid, idx) in curation.pmids" class="list-inline-item" :key="idx">
                                    {{pmid}}<span v-if="curation.pmids && curation.pmids.length > idx+1">,</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">Notes on Rationale</strong>
                        <div class="col-md">
                            {{curation.rationale_notes}}
                        </div>
                    </div>
                    <div class="row mt-2">
                        <strong class="col-md-2">MonDO ID:</strong> 
                        <div class="col-md">
                            {{ (curation.mondo_id) ? curation.mondo_id : '--'}}
                            {{ (curation.mondo_name ? curation.mondo_name : '')}}
                        </div>
                    </div>
                    <div class="row mt-1">
                        <strong class="col-md-2">Disease entity notes:</strong> 
                        <div class="col-md">{{ (curation.disease_entity_notes) ? curation.disease_entity_notes : '--' }}</div>
                    </div>
                    <div class="row mt-3">
                        <strong class="col-md-2">Current Status:</strong>
                        <div class="col-md-6">
                            <div class="mb-2">
                                {{ (curation.current_status) ? curation.current_status.name : 'No status set' }} 
                                <button class="btn btn-sm"><small><small @click="showStatusHistory = !showStatusHistory">{{statusHistoryButtonText}}</small></small></button>
                            </div>
                            <transition name="fade">
                                <curation-status-history :curation="curation" v-show="showStatusHistory"></curation-status-history>
                            </transition>
                        </div>
                    </div>
                    <div class="row mt-2" v-if="curation.gdm_uuid">
                        <strong class="col-md-2">GCI ID:</strong> 
                        <div class="col-md">
                            <a :href="`https://curation.clinicalgenome.org/curation-central/?gdm=${curation.gdm_uuid}`" 
                                target="gci"
                            >
                                {{ curation.gdm_uuid }}
                            </a>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <strong class="col-md-2">Current Classification:</strong>
                        <div class="col-md-6">
                            <div class="mb-2">
                                {{ (curation.current_classification) ? curation.current_classification.name : 'No status set' }} 
                                <button class="btn btn-sm"><small><small @click="showClassificationHistory = !showClassificationHistory">{{classificationButtonText}}</small></small></button>
                            </div>
                            <transition name="fade">
                                <classification-history :curation="curation" v-show="showClassificationHistory"></classification-history>
                            </transition>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <strong class="col-md-2">Notes:</strong> 
                        <div class="col-md">{{ (curation.notes) ? curation.notes : '--' }}</div>
                    </div>
                </div>
                <hr>
                <documents-card :curation="curation"></documents-card>
            </div>
        </b-card>
        <div v-else class="alert alert-secondary lead text-center mt-4" key="loading">
            Loading...
        </div>
        </transition>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'
    import PhenotypeList from './Phenotypes/List'
    import CurationStatusHistory from './StatusHistory'
    import ClassificationHistory from './ClassificationHistory'
    import DeleteButton from './DeleteButton'
    import DocumentsCard from './Documents/DocumentsCard'

    export default {
        props: ['id'],
        components: {
            PhenotypeList,
            CurationStatusHistory,
            DeleteButton,
            ClassificationHistory,
            DocumentsCard
        },
        data() {
            return {
                showStatusHistory: false,
                showClassificationHistory: false,
                loading: true
            }
        },
        watch: {
            '$route' (to, from) {
                // console.log(to);
                this.loadCuration()
            }
        },
        computed: {
            ...mapGetters({ user: 'getUser'}),
            ...mapGetters('curations', {
                curations: 'Items',
                getCuration: 'getItemById'
            }),            
            statusHistoryButtonText: function() {
                return (this.showStatusHistory) ? 'Hide history' : 'Show history';
            },
            classificationButtonText: function() {
                return (this.showClassificationHistory) ? 'Hide history' : 'Show history';
            },
            title: function () {
                let title = 'Curation: ';
                if (this.curation && this.curation.gene_symbol) {
                    title += this.curation.gene_symbol
                    if (this.curation.mondo_id) {
                        title += ' / ' + this.curation.mondo_id
                    }
                    if (this.curation.expert_panel) {
                        title += ' for '+this.curation.expert_panel.name
                    }
                }
                return title;
            },
            curation: function(){
                if (this.curations.length == 0) {
                    return {
                    }
                }
                return this.getCuration(this.id)
            },

        },
        methods: {
            ...mapActions('curations', {
                fetchCuration: 'fetchItem',
            }),
            loadCuration() {
                this.loading = true;
                this.fetchCuration(this.id)
                    .then(respones => {
                        this.loading = false;
                    })
                    .catch(error => {
                        this.loading = false;
                    });
            }
        },
        mounted: function () {
            this.loadCuration();
        }
    }
</script>