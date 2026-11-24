<style></style>
<template>
    <div class="card">
        <div class="card-header"> 
            <span class="float-end">
                <router-link :to="'/curations/export'" class="btn btn-secondary btn-sm">Download CSV</router-link>
            </span>
            <h3>{{ gene ? `Curations for ${gene}` : 'All Curations' }}</h3>
        </div>
            
        <div class="card-body">
            <p v-if="invalidGene" role="alert">Please provide a valid gene symbol.</p>
            <curations-table v-else :key="gene" :gene-symbol="gene"></curations-table>
            <router-link v-if="gene || invalidGene" to="/curations">View all curations</router-link>
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import CurationsTable from './Table.vue'

const props = defineProps({ geneSymbol: { default: undefined } })
// Vue Router decodes params and query values once. Do not decode them again.
const gene = computed(() => typeof props.geneSymbol === 'string' ? props.geneSymbol.trim() : '')
const invalidGene = computed(() => props.geneSymbol !== undefined && (
    !gene.value || !/^[A-Za-z0-9]+(?:[.-][A-Za-z0-9]+)*$/.test(gene.value)
))
</script>
