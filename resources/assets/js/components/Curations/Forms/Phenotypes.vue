<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useStore } from 'vuex'
import CriteriaTable from './../CriteriaTable.vue'
import CurationNotifications from './ExistingCurationNotification.vue'
import ValidationError from '../../ValidationError.vue'
import useCurationForm from '../../../composables/useCurationForm'
import usePhenotypeList from '../../../composables/usePhenotypeList'

const props = defineProps(['modelValue', 'errors', 'disabled'])
const emit = defineEmits(['update:modelValue', 'auto-save'])

const store = useStore()
const page = 'phenotypes'
const { updatedCuration } = useCurationForm(props, emit, page, {})
const { phenotypes, loading, fetchPhenotypes } = usePhenotypeList()

const fields = [
    {
        key: 'phenotype',
        sortable: true
    },
    {
        key: 'phenotypeMimNumber',
        sortable: true
    },
    {
        key: 'phenotypeInheritance',
        sortable: true,
        label: 'Inheritance'
    },
    {
        key: 'checkbox',
        tdClass: 'text-end w-10',
        sortable: false,
        label: ' ',
        formatter: ({item}) => {
            return {
                'id': item.id,
                'mim_number': item.phenotypeMimNumber,
                'name': item.phenotype,
                'label_obsolete': Boolean(item.label_obsolete),
            }
        }
    }
]
const message = ref(null)
const rationales = computed(() => store.getters['rationales/Items'])

watch(updatedCuration, (to, from) => {
    if (to.gene_symbol != from.gene_symbol) {
        fetchPhenotypes(updatedCuration.value.id)
            .then(() => {
                const onePhenotype = Array.isArray(phenotypes.value) && phenotypes.value.length === 1
                const singleFromList = updatedCuration.value?.curation_type_id === 1
                const noPhenotypes = !Array.isArray(updatedCuration.value.phenotypes) || updatedCuration.value.phenotypes.length === 0
                if (onePhenotype && singleFromList && noPhenotypes) {
                    const p = phenotypes.value[0]
                    if (!Array.isArray(updatedCuration.value.phenotypes)) {
                        updatedCuration.value.phenotypes = []
                    }

                    updatedCuration.value.phenotypes[0] = {
                        id: p.id,
                        mim_number: p.phenotypeMimNumber,
                        name: p.phenotype,
                        label_obsolete: Boolean(p.label_obsolete),
                    }

                    if (!Array.isArray(updatedCuration.value.rationales)) {
                        updatedCuration.value.rationales = []
                    }

                    if (updatedCuration.value.rationales.length === 0) {
                        const defaultRationale = rationales.value.find(r => r.id === 6)
                        if (defaultRationale) {
                            updatedCuration.value.rationales.push(defaultRationale)
                        }
                    }

                    message.value = 'We have preselected the phenotype because you indicated you are curating ' + updatedCuration.value.gene_symbol + ' with this single disease entity'
                    nextTick(() => {
                        emit('auto-save')
                    })
                }
            })
    }
})

const showTable = computed(() => {
    return updatedCuration.value.curation_type_id != 2 && updatedCuration.value.curation_type_id != 3 && phenotypes.value.length > 0
})

const showRationale = true
</script>
<template>
    <div class="component-container">
        <div>
            <div class="alert alert-info" v-show="loading && phenotypes.length < 1">Loading phenotype information...</div>
            <div  v-show="!loading || phenotypes.length > 0">
                <div class="alert alert-secondary clearfix" v-show="phenotypes.length == 0">
                    <p>The gene <strong>{{ updatedCuration.value }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                </div>

                <div v-if="showTable && (phenotypes || []).some(p => p.label_obsolete)" class="alert alert-warning py-2">
                    Some phenotypes are not present in the latest OMIM list. They may have been renamed.
                </div>
                <b-table v-show="showTable"
                    :items="phenotypes"
                    :fields="fields"
                    stacked="sm"
                    striped 
                    hover 
                    small
                >
                    <template v-slot:head(checkbox)="data">
                        &nbsp;&nbsp;&nbsp;&nbsp;
                    </template>
                    <template v-slot:cell(phenotype)="data">
                        <span>{{ data.item.phenotype }}</span>
                        <span v-if="data.item.label_obsolete" class="badge bg-warning text-dark ms-1">Not in latest OMIM</span>
                    </template>
                    <template v-slot:cell(checkbox)="data">
                        <input 
                            v-if="!data.item.label_obsolete"
                            class="form-check-input form-check-input-lg"
                            type="checkbox" 
                            v-model="updatedCuration.phenotypes"
                            :value="data.value"
                            :disabled="disabled"
                        >
                    </template>
                </b-table>
                <curation-notifications :curation="updatedCuration" class="mt-2"></curation-notifications>
                <div class="alert alert-info" v-show="message">{{message}}</div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="form-group" v-if="showRationale">
                    <label for="rationale_id">What is your rationale for this curation?</label>
                    <select v-model="updatedCuration.rationales" 
                        multiple class="form-control" 
                        style="height: 8.5em"
                    >
                        <option v-for="rationale in rationales" :key="rationale.id"
                            :value="rationale"
                        >
                            {{ rationale.name }}
                        </option>
                    </select>
                    <validation-error :messages="errors.rationales"></validation-error>
                </div>
                <transition name="fade">
                    <div class="form-group" v-show="updatedCuration.rationale_id == 100">
                        <textarea v-model="updatedCuration.rationale_other" placeholder="Other rationale details" class="form-control"></textarea>
                        <validation-error :messages="errors.rationale_other"></validation-error>
                    </div>
                </transition>
                <div class="form-group" v-show="updatedCuration.curation_type_id != 3">
                    <label for="pmids">Supporting PMIDS:</label>
                    <small>comma separated list</small>
                    <input id="pmids" v-model="updatedCuration.pmids" class="form-control" placeholder="18183754, 123451, 1231231">
                    <validation-error :messages="errors.pmids"></validation-error>
                </div>
                <div class="form-group" v-show="updatedCuration.curation_type_id == 3">
                    <label for="isolated_phenotype">Enter broader OMIM phenotype (MIM phenotype):</label>
                    <input id="isolated_phenotype" v-model="updatedCuration.isolated_phenotype" class="form-control">
                    <validation-error :messages="errors.isolated_phenotype"></validation-error>
                </div>
                <div class="form-group">
                    <label for="rationale_notes">Provide your Rationale:</label>
                    <textarea id="rationale_notes" v-model="updatedCuration.rationale_notes" class="form-control"></textarea>
                    <validation-error :messages="errors.rationale_notes"></validation-error>
                </div>
            </div>
            <div class="col-lg-4" v-show="showTable">
                <criteria-table></criteria-table>
            </div>
        </div>
    </div>
</template>
