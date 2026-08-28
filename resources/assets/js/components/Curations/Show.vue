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
                <template #header>
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
                            <delete-button v-if="user.canDeleteCuration(curation)" class="btn btn-sm" :curation="curation"></delete-button>
                            <transfer-curation-control 
                                :curation="curation" 
                                v-if="transferEnabled && user.canEditCuration(curation)"
                            ></transfer-curation-control>
                        </div>
                        <div v-if="curation.is_archived" class="alert alert-warning mt-2 mb-0">
                            <p>The group has indicated this curation is no longer applicable, and has marked the curation as archived for historical purposes.</p>
                            
                            <strong>This curation was archived on {{ curation.archived_at }}.</strong>
                            <template v-if="curation.archive_reason">
                                 <br />
                                 <strong>Reason:</strong> {{ curation.archive_reason }}
                            </template>
                            <template v-if="curation.gcex_url">
                                <br /> 
                                <strong>GCEx URL:</strong>
                                <external-link :href="curation.gcex_url" target="gcex_url" class="external">
                                    {{curation.gcex_url}}
                                </external-link>
                            </template>
                        </div>
                    </div>
                </template>
                <div v-if="curations">
                    <div id="info">
                        <div class="row mt-2">
                            <strong class="col-md-3">Precuration ID:</strong>
                            <div class="col-md">
                                {{curation.id}}
                            </div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">Gene Symbol:</strong> 
                            <div class="col-md">{{ curation.gene_symbol }} - <span v-if="curation.name">{{`hgnc:${curation.name}`}}</span> (<small v-if="curation.hgnc_id">{{`hgnc:${curation.hgnc_id}`}}</small>)</div>
                        </div>
                        <div class="row mt-2">
                            <strong class="col-md-3">
                                Mode Of Inheritance:
                            </strong>
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
                            <div class="col-md">
                                {{curation.rationale_notes}}
                            </div>
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
                                        <small>
                                            <small @click="showStatusHistory = !showStatusHistory">
                                                {{statusHistoryButtonText}}
                                            </small>
                                        </small>
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
                                <div class="mt-1 small text-muted">This pre-curation is linked to a record in the GCI.  To transfer this record to another expert panel please contact GCI support at <a href="mailto:clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a></div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <strong class="col-md-3">Current Classification:</strong>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    {{ (curation.current_classification) ? curation.current_classification.name : 'Not yet classified' }} 
                                    <button class="btn btn-sm" v-if="curation.current_classification">
                                        <small>
                                            <small @click="showClassificationHistory = !showClassificationHistory">
                                                {{classificationButtonText}}
                                            </small>
                                        </small>
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
                    <archived-curation-links :model-value="curation" :editable="false" />
                    <hr>
                    <documents-card :curation="curation"></documents-card>
                    
                    <hr>
                    <notes-list :notes="curation.notes">
                        <template #title>Administrative Notes</template>
                    </notes-list>
                </div>
            </b-card>
            <div v-else class="alert alert-secondary lead text-center mt-4" key="loading">
                Loading...
            </div>
        </transition>
    </div>
</template>
<script setup>
    import { computed, onMounted, ref, watch } from 'vue'
    import { useRoute } from 'vue-router'
    import { useStore } from 'vuex'
    import PhenotypeList from './Phenotypes/List.vue'
    import NotesList from '../NotesList.vue'
    import HistoryTable from './HistoryTable.vue'
    import ClassificationHistory from './ClassificationHistory.vue'
    import DeleteButton from './DeleteButton.vue'
    import ArchivedCurationLinks from './Forms/ArchivedCurationLinks.vue'
    import DocumentsCard from './Documents/DocumentsCard.vue'
    import TransferCurationControl from './TransferCurationControl.vue'
    import GciLink from '../Curations/GciLink.vue'
    import ToggleButton from '../buttons/ToggleButton.vue'

    const props = defineProps(['id'])

    const route = useRoute()
    const store = useStore()

    const showOwnerHistory = ref(false)
    const showStatusHistory = ref(false)
    const showClassificationHistory = ref(false)
    const loading = ref(true)
    let loadSequence = 0

    const user = computed(() => store.getters.getUser)
    const curations = computed(() => store.getters['curations/Items'])
    const curation = computed(() => store.getters['curations/currentItem'] || {})
    const transferEnabled = computed(() => store.state.features.transferEnabled)

    const statusHistoryButtonText = computed(() => (
        showStatusHistory.value ? 'Hide history' : 'Show history'
    ))
    const classificationButtonText = computed(() => (
        showClassificationHistory.value ? 'Hide history' : 'Show history'
    ))
    const title = computed(() => {
        let value = 'Curation: '
        if (curation.value.gene_symbol) {
            value += curation.value.gene_symbol
            if (curation.value.mondo_id) {
                value += ' / ' + curation.value.mondo_id
            }
            if (curation.value.expert_panel) {
                value += ' for ' + curation.value.expert_panel.name
            }
        }
        return value
    })
    const mondoUrl = computed(() => {
        if (curation.value.mondo_id) {
            return `https://www.ebi.ac.uk/ols/ontologies/mondo/terms?iri=http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FMONDO_${curation.value.mondo_id.substring(6)}`
        }
        return undefined
    })

    const loadCuration = id => {
        const sequence = ++loadSequence
        loading.value = true

        return store.dispatch('curations/fetchItem', id)
            .catch(() => {})
            .finally(() => {
                if (sequence === loadSequence) {
                    loading.value = false
                }
            })
    }

    watch(
        () => route.fullPath,
        () => loadCuration(route.params.id)
    )

    onMounted(() => loadCuration(props.id))
</script>
