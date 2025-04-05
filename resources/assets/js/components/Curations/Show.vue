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
            <q-card id="show-curation" v-else key="curation-details" style="max-height: 1000px">
                <q-card-section>
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
                                v-if="store.state.features.transferEnabled"></transfer-curation-control>
                        </div>
                    </div>
                </q-card-section>
                <q-card-section>
                    <div v-if="curations">
                        <dl id="info">
                            <div>
                                <dt>Precuration ID:</dt>
                                <dd>{{ curation.id }}</dd>
                            </div>
                            <div class="content-row">
                                <dt>Gene Symbol:</dt>
                                <dd>
                                    {{ curation.gene_symbol }} -
                                    <span v-if="curation.name">{{ `hgnc:${curation.name}` }}</span>
                                    (<small v-if="curation.hgnc_id">{{ `hgnc:${curation.hgnc_id}` }}</small>)
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>
                                    Mode Of Inheritance:
                                </dt>
                                <dd>
                                    <div v-if="curation.mode_of_inheritance">
                                        {{ curation.mode_of_inheritance.name }} -
                                        ({{ curation.mode_of_inheritance.hp_id }})
                                    </div>
                                    <div v-else>--</div>
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Disease Entity:</dt>
                                <dd>
                                    <div v-if="curation.mondo_id">
                                        <external-link :href="mondoUrl" target="mondo" class="external">
                                            <span v-if="curation.disease && curation.disease.name">
                                                {{ (curation.disease.name ? curation.disease.name : '') }} -
                                            </span>
                                            {{ (curation.mondo_id) ? curation.mondo_id : '--' }}
                                        </external-link>
                                    </div>
                                </dd>
                            </div>
                            <hr>
                            <div class="content-row">
                                <dt>Expert Panel:</dt>
                                <dd>
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
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Curator:</dt>
                                <dd>{{ (curation.curator) ? curation.curator.name : '--' }}</dd>
                            </div>
                            <div class="content-row">
                                <dt>Curation Type:</dt>
                                <dd>
                                    {{ curation.curation_type ? curation.curation_type.description : '--' }}
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Phenotypes:</dt>
                                <dd>
                                    <phenotype-list :curation="curation" :gene-symbol="curation.gene_symbol" />
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Rationale:</dt>
                                <dd>
                                    <ul class="list-inline">
                                        <li v-for="(rationale, idx) in curation.rationales" :key="rationale.id"
                                            class="list-inline-item">
                                            {{ rationale.name }}<span
                                                v-if="idx + 1 < curation.rationales.length">,</span>
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>PMIDS</dt>
                                <dd v-if="curation.pmids">
                                    <ul class="list-inline">
                                        <li v-for="(pmid, idx) in curation.pmids" class="list-inline-item" :key="idx">
                                            {{ pmid }}<span
                                                v-if="curation.pmids && curation.pmids.length > idx + 1">,</span>
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Notes on Rationale</dt>
                                <dd>
                                    {{ curation.rationale_notes }}
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Disease entity notes:</dt>
                                <dd>{{ (curation.disease_entity_notes) ? curation.disease_entity_notes :
                                    '--' }}</dd>
                            </div>
                            <div class="content-row">
                                <dt>Current Status:</dt>
                                <dd>
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
                                </dd>
                            </div>
                            <div class="content-row" v-if="curation.gdm_uuid">
                                <dt>GCI ID:</dt>
                                <dd>
                                    <gci-link :curation="curation"></gci-link>
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Current Classification:</dt>
                                <dd>
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
                                </dd>
                            </div>
                            <div class="content-row">
                                <dt>Notes:</dt>
                                <dd>{{ (curation.curation_notes) ? curation.curation_notes : '--' }}
                                </dd>
                            </div>
                        </dl>
                        <hr>

                        <documents-card :curation="curation"></documents-card>

                        <hr>
                        <notes-list :notes="curation.notes">
                            <div slot="title">Administrative Notes</div>
                        </notes-list>
                    </div>
                </q-card-section>
            </q-card>
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

.content-row dt {
    font-weight: bold;
    width: 30%;
}

.content-row dd {
    width: 70%;
}
</style>