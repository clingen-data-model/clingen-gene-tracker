<style scoped>
    .unused {
        color: #aaa;
        /*text-decoration: line-through;*/
    }
</style>
<template>
    <div class="component-container row">
        <div v-if="phenotypes.length > 0" class=" col-lg-8">
            
            <div v-if="phenotypes.some(p => p.label_obsolete)" class="alert alert-warning py-2 small">
                Some phenotypes are not present in the latest OMIM file. They may have been renamed.
            </div>

            <table class="table table-sm table-xs mb-0">
                <thead>
                    <tr>
                    <th>MIM Number</th>
                    <th style="width: 80%">Phenotype</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="phenotype in phenotypes" :key="phenotype.id">
                        <td>{{ phenotype.mim_number }}</td>
                        <td>{{ phenotype.name }} <span v-if="phenotype.label_obsolete" class="badge bg-warning text-dark ms-1">Not in latest OMIM</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col" v-else>
            No phenotypes in this curation
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({
    geneSymbol: {
        required: true,
    },
    curation: {
        required: true,
        type: Object
    }
})

const phenotypes = computed(() => props.curation.phenotypes || [])
</script>
