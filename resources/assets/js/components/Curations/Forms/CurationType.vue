<style></style>
<template>
    <div class="curation-curation-container">
            <div v-show="phenotypes.length == 0 && !loading">
                <div class="alert alert-secondary clearfix">
                    <p>The gene <strong>{{ updatedCuration.gene_symbol }}</strong> is not associated with a disease entity per OMIM at this time.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <omim-loading></omim-loading>
                    <transition name="fade">
                        <div v-show="phenotypes.length > 0">
                            <b-table striped hover :items="phenotypes" :fields="fields" stacked="sm" small bordered>
                                <template v-slot:cell(phenotype)="data">
                                    <span>{{ data.item.phenotype }}</span>
                                    <span v-if="data.item.label_obsolete" class="badge bg-warning text-dark ms-1">Not in latest OMIM</span>
                                </template>
                            </b-table>
                            <div class="form-group">
                                <label><strong>How would you like to proceed?</strong></label>
                                <b-form-radio-group id="btnradios2"
                                    size="lg"
                                    v-model="updatedCuration.curation_type_id"
                                    :options="options"
                                    stacked
                                    name="radioBtnOutline" />
                                <validation-error :messages="errors.curation_type_id"></validation-error>
                            </div>
                        </div>
                    </transition>
                </div>
                <div class="col-lg-4">
                    <criteria-table></criteria-table>
                </div>
            </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import CriteriaTable from './../CriteriaTable.vue'
import ValidationError from '../../ValidationError.vue'
import OmimLoading from '../../OmimLoading.vue'
import useCurationForm from '../../../composables/useCurationForm'
import usePhenotypeList from '../../../composables/usePhenotypeList'

const props = defineProps(['modelValue', 'errors'])
const emit = defineEmits(['update:modelValue'])

const page = 'curation-types'
const { updatedCuration } = useCurationForm(props, emit, page)
const {
    phenotypes,
    phenotypesLoaded,
    loading,
    fetchPhenotypes
} = usePhenotypeList()

const curationTypes = ref([])
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
]

watch(updatedCuration, (to, from) => {
    if (to != from) {
        if (to.gene_symbol != from.gene_symbol || to.curation_type_id != from.curation_type_id) {
            fetchPhenotypes(updatedCuration.value.id)
        }
        updatedCuration.value.addingCurationType = 1
    }
})

const nonObsoletePhenotypes = computed(() => {
    return (phenotypes.value || []).filter(p => !p.label_obsolete)
})

const options = computed(() => {
    const activePhenotypes = nonObsoletePhenotypes.value
    if (phenotypesLoaded.value && activePhenotypes.length == 0 && updatedCuration.value.curation_type_id === null) {
        updatedCuration.value.curation_type_id = 2
        return []
    }
    if (activePhenotypes.length == 1) {
        return curationTypes.value
            .filter(item => item.name != 'lumped')
            .map(item => ({text: item.description, value: item.id}))
    }
    return curationTypes.value
        .map(item => ({text: item.description, value: item.id}))
})

function fetchCurationTypes() {
    window.axios.get('/api/curation-types')
        .then((response) => {
            curationTypes.value = response.data
        })
}

onMounted(() => {
    fetchCurationTypes()
})
</script>
