<template>
    <div class="curation-show-container">
        <div>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>
        </div>
        <transition name="fade">
            <div
                id="show-curation"
                class="card"
                v-if="!loading"
                key="curation-details"
                style="max-height: 1000px"
            >
                <div class="card-header">
                    <div class="d-float justify-content-between">
                        <h3> {{ title }}</h3>
                        <div class="d-flex space-x-1" v-if="!loading">
                            <router-link
                                v-if="user.canEditCuration(curation)"
                                :id="'edit-curation-'+curation.id+'-btn'"
                                class="btn btn-secondary btn-sm"
                                :to="'/curations/'+curation.id+'/edit'"
                            >
                                Edit
                            </router-link>
                            <delete-button class="btn btn-sm" :curation="curation"></delete-button>
                            <transfer-curation-control
                                :curation="curation"
                                v-if="transferEnabled"
                            ></transfer-curation-control>
                        </div>
                    </div>
                </div>
                <div class="card-body" v-if="curations">
                    <div id="info">
                        <div class="row mt-2">
                            <strong class="col-md-3">Precuration ID:</strong>
                            <div class="col-md">{{curation.id}}</div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Gene Symbol:</strong>
                            <div class="col-md">{{ curation.gene_symbol }} - <span v-if="curation.name">{{`hgnc:${curation.name}`}}</span> (<small v-if="curation.hgnc_id">{{`hgnc:${curation.hgnc_id}`}}</small>)</div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Mode Of Inheritance:</strong>
                            <div class="col-md">
                                <div v-if="curation.mode_of_inheritance">
                                    {{curation.mode_of_inheritance.name}} - ({{curation.mode_of_inheritance.hp_id}})
                                </div>
                                <div v-else>--</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Disease Entity:</strong>
                            <div class="col-md">
                                <div v-if="curation.mondo_id">
                                    <external-link :href="mondoUrl" target="mondo" class="external">
                                        <span v-if="curation.disease && curation.disease.name">
                                            {{ (curation.disease.name ? curation.disease.name : '')}} -
                                        </span>
                                        {{ (curation.mondo_id) ? curation.mondo_id : '--'}}
                                    </external-link>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mt-2">
                            <strong class="col-md-3">Expert Panel:</strong>
                            <div class="col-md">
                                {{ (curation.expert_panel) ? curation.expert_panel.name : '--'}}
                                <div v-if="transferEnabled">
                                    <toggle-button
                                        v-model="showOwnerHistory"
                                        show-label="Show history"
                                        hide-label="Hide history"
                                    ></toggle-button>
                                    <transition name="fade">
                                        <history-table
                                            :items="curation.expert_panels"
                                            item-label="Expert Panel"
                                            date-field="start_date"
                                            v-show="showOwnerHistory"
                                            index-attribute="id"
                                        ></history-table>
                                    </transition>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Curator:</strong>
                            <div class="col-md">{{ (curation.curator) ? curation.curator.name : '--'}}</div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Curation Type:</strong>
                            <div class="col-md">
                                {{curation.curation_type ? curation.curation_type.description : '--'}}
                            </div>
                        </div>
                        <div class="row mt-4">
                            <strong class="col-md-3">Phenotypes:</strong>
                            <phenotype-list :curation="curation" :gene-symbol="curation.gene_symbol" class="col-md"></phenotype-list>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Rationale:</strong>
                            <div class="col-md">
                                <ul class="list-inline">
                                    <li v-for="(rationale, idx) in curation.rationales" :key="rationale.id" class="list-inline-item">
                                        {{rationale.name}}<span v-if="idx+1 < curation.rationales.length">,</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">PMIDS</strong>
                            <div class="col-md" v-if="curation.pmids">
                                <ul class="list-inline">
                                    <li v-for="(pmid, idx) in curation.pmids" class="list-inline-item" :key="idx">
                                        {{pmid}}<span v-if="curation.pmids && curation.pmids.length > idx+1">,</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Notes on Rationale</strong>
                            <div class="col-md">{{curation.rationale_notes}}</div>
                        </div>
                        <div class="row mt-1">
                            <strong class="col-md-3">Disease entity notes:</strong>
                            <div class="col-md">{{ (curation.disease_entity_notes) ? curation.disease_entity_notes : '--' }}</div>
                        </div>
                        <div class="row mt-3">
                            <strong class="col-md-3">Current Status:</strong>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    {{ (curation.current_status) ? curation.current_status.name : 'No status set' }}
                                    <button class="btn btn-sm">
                                        <small><small @click="showStatusHistory = !showStatusHistory">{{statusHistoryButtonText}}</small></small>
                                    </button>
                                </div>
                                <transition name="fade">
                                    <history-table
                                        :items="curation.curation_statuses"
                                        item-label="Status"
                                        date-field="status_date"
                                        v-show="showStatusHistory"
                                    ></history-table>
                                </transition>
                            </div>
                        </div>
                        <div class="row mt-2" v-if="curation.gdm_uuid">
                            <strong class="col-md-3">GCI ID:</strong>
                            <div class="col-md">
                                <gci-link :curation="curation"></gci-link>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <strong class="col-md-3">Current Classification:</strong>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    {{ (curation.current_classification) ? curation.current_classification.name : 'Not yet classified' }}
                                    <button class="btn btn-sm" v-if="curation.current_classification">
                                        <small><small @click="showClassificationHistory = !showClassificationHistory">{{classificationButtonText}}</small></small>
                                    </button>
                                </div>
                                <transition name="fade">
                                    <div>
                                        <classification-history :curation="curation" v-show="showClassificationHistory"></classification-history>
                                    </div>
                                </transition>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <strong class="col-md-3">Notes:</strong>
                            <div class="col-md">{{ (curation.curation_notes) ? curation.curation_notes : '--' }}</div>
                        </div>
                    </div>
                    <hr>
                    <documents-card :curation="curation"></documents-card>

                    <hr>
                    <notes-list :notes="curation.notes">
                        <template #title>Administrative Notes</template>
                    </notes-list>
                </div>
            </div>
            <div v-if="loading" class="alert alert-secondary lead text-center mt-4" key="loading">
                Loading...
            </div>
        </transition>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useAppStore } from '../../stores/app'
    import { useCurationsStore } from '../../stores/curations'
    import PhenotypeList from './Phenotypes/List.vue'
    import NotesList from '../NotesList.vue'
    import HistoryTable from './HistoryTable.vue'
    import CurationStatusHistory from './StatusHistory.vue'
    import ClassificationHistory from './ClassificationHistory.vue'
    import DeleteButton from './DeleteButton.vue'
    import DocumentsCard from './Documents/DocumentsCard.vue'
    import TransferCurationControl from './TransferCurationControl.vue'
    import GciLink from '../Curations/GciLink.vue'
    import ToggleButton from '../buttons/ToggleButton.vue'

    export default {
        props: ['id'],
        components: {
            PhenotypeList,
            CurationStatusHistory,
            DeleteButton,
            ClassificationHistory,
            DocumentsCard,
            TransferCurationControl,
            GciLink,
            HistoryTable,
            ToggleButton,
            NotesList
        },
        data() {
            return {
                showOwnerHistory: false,
                showStatusHistory: false,
                showClassificationHistory: false,
                loading: true
            }
        },
        watch: {
            '$route' (to, from) {
                this.loadCuration()
            }
        },
        computed: {
            ...mapState(useAppStore, {user: 'getUser'}),
            ...mapState(useCurationsStore, {
                curations: 'Items',
                getCuration: 'getItemById',
                curation: 'currentItem'
            }),
            transferEnabled() {
                const appStore = useAppStore()
                return appStore.features.transferEnabled
            },
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
            mondoUrl: function () {
                if (this.curation.mondo_id) {
                    return `https://www.ebi.ac.uk/ols/ontologies/mondo/terms?iri=http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FMONDO_${this.curation.mondo_id.substring(6)}`
                }
            }
        },
        methods: {
            ...mapActions(useCurationsStore, {
                fetchCuration: 'fetchItem',
            }),
            loadCuration() {
                this.loading = true;
                this.fetchCuration(this.id)
                    .then(response => {
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
