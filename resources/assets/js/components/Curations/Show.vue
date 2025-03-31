<template>
    <div class="curation-show-container">
        <div>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>

        </div>
        <transition name="fade">
            <div v-if="loading" class="alert alert-secondary lead text-center mt-4" key="loading">
                Loading...
            </div>
            <Card id="show-curation" v-else key="curation-details" style="max-height: 1000px">
                <template #header>
                    <div class="float">
                        <h3> {{ title }}</h3>

                        <div class="flex" v-if="!loading">
                            <router-link v-if="user.canEditCuration(curation)"
                                :id="'edit-curation-' + curation.id + '-btn'" class="btn btn-secondary btn-sm"
                                :to="'/curations/' + curation.id + '/edit'">
                                Edit
                            </router-link>
                            <delete-button :curation="curation"></delete-button>
                            <transfer-curation-control :curation="curation"
                                v-if="$store.state.features.transferEnabled"></transfer-curation-control>
                        </div>
                    </div>
                </template>
                <template #content>
                    <div v-if="curations">
                        <div id="info">
                            <div class="content-row">
                                <strong class="content-key">Precuration ID:</strong>
                                <div class="content-value">
                                    {{ curation.id }}
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Gene Symbol:</strong>
                                <div class="content-value">{{ curation.gene_symbol }} - <span v-if="curation.name">{{
                                    `hgnc:${curation.name}`
                                        }}</span> (<small v-if="curation.hgnc_id">{{ `hgnc:${curation.hgnc_id}`
                                        }}</small>)
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">
                                    Mode Of Inheritance:
                                </strong>
                                <div class="content-value">
                                    <div v-if="curation.mode_of_inheritance">
                                        {{ curation.mode_of_inheritance.name }} - ({{ curation.mode_of_inheritance.hp_id
                                        }})
                                    </div>
                                    <div v-else>--</div>
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Disease Entity:</strong>
                                <div class="content-value">
                                    <div v-if="curation.mondo_id">
                                        <external-link :href="mondoUrl" target="mondo" class="external">
                                            <span v-if="curation.disease && curation.disease.name">
                                                {{ (curation.disease.name ? curation.disease.name : '') }} -
                                            </span>
                                            {{ (curation.mondo_id) ? curation.mondo_id : '--' }}
                                        </external-link>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="content-row">
                                <strong class="content-key">Expert Panel:</strong>
                                <div class="content-value">
                                    {{ (curation.expert_panel) ? curation.expert_panel.name : '--' }}
                                    <div v-if="$store.state.features.transferEnabled">
                                        <!-- <pre>{{curation.expert_panels}}</pre> -->
                                        <toggle-button v-model="showOwnerHistory" show-label="Show history"
                                            hide-label="Hide history"></toggle-button>
                                        <transition name="fade">
                                            <history-table :items="curation.expert_panels" item-label="Expert Panel"
                                                date-field="start_date" v-show="showOwnerHistory"
                                                index-attribute="id"></history-table>
                                        </transition>
                                    </div>
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Curator:</strong>
                                <div class="content-value">{{ (curation.curator) ? curation.curator.name : '--' }}</div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Curation Type:</strong>
                                <div class="content-value">
                                    {{ curation.curation_type ? curation.curation_type.description : '--' }}
                                </div>
                            </div>
                            <div class="row mt-4">
                                <strong class="content-key">Phenotypes:</strong>
                                <phenotype-list :curation="curation" :gene-symbol="curation.gene_symbol"
                                    class="content-value"></phenotype-list>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Rationale:</strong>
                                <div class="content-value">
                                    <ul class="list-inline">
                                        <li v-for="(rationale, idx) in curation.rationales" :key="rationale.id"
                                            class="list-inline-item">
                                            {{ rationale.name }}<span
                                                v-if="idx + 1 < curation.rationales.length">,</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">PMIDS</strong>
                                <div class="content-value" v-if="curation.pmids">
                                    <ul class="list-inline">
                                        <li v-for="(pmid, idx) in curation.pmids" class="list-inline-item" :key="idx">
                                            {{ pmid }}<span
                                                v-if="curation.pmids && curation.pmids.length > idx + 1">,</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="content-row">
                                <strong class="content-key">Notes on Rationale</strong>
                                <div class="content-value">
                                    {{ curation.rationale_notes }}
                                </div>
                            </div>
                            <div class="row mt-1">
                                <strong class="content-key">Disease entity notes:</strong>
                                <div class="content-value">{{ (curation.disease_entity_notes) ? curation.disease_entity_notes :
                                    '--' }}</div>
                            </div>
                            <div class="row mt-3">
                                <strong class="content-key">Current Status:</strong>
                                <div class="content-value">
                                    <div class="mb-2">
                                        {{ (curation.current_status) ? curation.current_status.name : 'No status set' }}
                                        <button class="btn btn-sm">
                                            <small>
                                                <small @click="showStatusHistory = !showStatusHistory">
                                                    {{ statusHistoryButtonText }}
                                                </small>
                                            </small>
                                        </button>
                                    </div>
                                    <transition name="fade">
                                        <history-table :items="curation.curation_statuses" item-label="Status"
                                            date-field="status_date" v-show="showStatusHistory"></history-table>
                                    </transition>
                                </div>
                            </div>
                            <div class="content-row" v-if="curation.gdm_uuid">
                                <strong class="content-key">GCI ID:</strong>
                                <div class="grid grid-cols-12 gap-4">
                                    <gci-link :curation="curation"></gci-link>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <strong class="content-key">Current Classification:</strong>
                                <div class="col-span-6">
                                    <div class="mb-2">
                                        {{ (curation.current_classification) ? curation.current_classification.name :
                                            'Not yet classified'
                                        }}
                                        <button class="btn btn-sm" v-if="curation.current_classification">
                                            <small>
                                                <small @click="showClassificationHistory = !showClassificationHistory">
                                                    {{ classificationButtonText }}
                                                </small>
                                            </small>
                                        </button>
                                    </div>
                                    <transition name="fade">
                                        <div>
                                            <classification-history :curation="curation"
                                                v-show="showClassificationHistory"></classification-history>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <strong class="content-key">Notes:</strong>
                                <div class="grid grid-cols-12 gap-4">{{ (curation.curation_notes) ? curation.curation_notes : '--' }}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <documents-card :curation="curation"></documents-card>

                        <hr>
                        <notes-list :notes="curation.notes">
                            <div slot="title">Administrative Notes</div>
                        </notes-list>
                    </div>
                </template>
            </Card>
        </transition>
    </div>
</template>
<script setup>
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
import ExternalLink from '../ExternalLink.vue'
import Card from 'primevue/card'

defineProps({
    id: {
        type: String,
        required: true
    }
})

const showOwnerHistory = ref(false)
const showStatusHistory = ref(false)
const showClassificationHistory = ref(false)
const loading = ref(true)

const route = useRoute()
const store = useStore()

watch(route, (to, from) => {
    loadCuration()

})

const user = computed(() => {
    return store.getters.getUser || {}
})
const curations = computed(() => {
    return store.getters['curations/Items'] || []
})
const getCuration = computed(() => {
    return store.getters['curations/getItemById'](route.params.id) || {}
})
const curation = computed(() => {
    return store.getters['curations/currentItem'] || {}
})
const statusHistoryButtonText = computed(() => {
    return (showStatusHistory.value) ? 'Hide history' : 'Show history'
})
const classificationButtonText = computed(() => {
    return (showClassificationHistory.value) ? 'Hide history' : 'Show history'
})
const title = computed(() => {
    let title = 'Curation: ';
    if (curation.value && curation.value.gene_symbol) {
        title += curation.value.gene_symbol
        if (curation.value.mondo_id) {
            title += ' / ' + curation.value.mondo_id
        }
        if (curation.value.expert_panel) {
            title += ' for ' + curation.value.expert_panel.name
        }
    }
    return title;
})
const mondoUrl = computed(() => {
    if (curation.value.mondo_id) {
        return `https://www.ebi.ac.uk/ols/ontologies/mondo/terms?iri=http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FMONDO_${curation.value.mondo_id.substring(6)}`
    }
})

const loadCuration = () => {
    loading.value = true
    store.dispatch('curations/fetchItem', route.params.id)
        .then(response => {
            loading.value = false
        })
        .catch(error => {
            loading.value = false
        })
}

onMounted(() => {
    loadCuration()
})
</script>

<style scoped>
.content-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}
.content-key {
    font-weight: bold;
    width: 30%;
}
.content-value {
    width: 70%;
}
</style>