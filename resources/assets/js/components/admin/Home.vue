<template>
    <div>
        <h2>Administration Dashboard</h2>
        <b-alert v-if="errorMessage" variant="danger" show>{{ errorMessage }}</b-alert>
        <div v-if="loading" class="text-center my-4"><b-spinner label="Loading dashboard" /></div>
        <div v-else class="row">
            <div v-for="card in cards" :key="card.label" class="col-md-4 mb-3">
                <b-card>
                    <div class="h2 mb-0">{{ card.value }}</div>
                    <div class="text-muted">{{ card.label }}</div>
                    <router-link class="btn btn-sm btn-link px-0" :to="card.to">View report »</router-link>
                </b-card>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const counts = ref({ outdated_phenotypes: 0, affected_curations: 0, outdated_phenotypes_in_use: 0 })
const loading = ref(true)
const errorMessage = ref('')
const cards = computed(() => [
    { label: 'Outdated Phenotype Labels', value: counts.value.outdated_phenotypes, to: { name: 'admin-outdated-phenotypes', query: { tab: 'phenotypes' } } },
    { label: 'Affected Curations', value: counts.value.affected_curations, to: { name: 'admin-outdated-phenotypes', query: { tab: 'curations' } } },
    { label: 'Outdated Phenotype Labels used on Curations', value: counts.value.outdated_phenotypes_in_use, to: { name: 'admin-outdated-phenotypes', query: { tab: 'phenotypes' } } },
])

onMounted(async () => {
    try {
        counts.value = (await window.axios.get('/api/admin/dashboard')).data
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load the administration dashboard.'
    } finally {
        loading.value = false
    }
})
</script>
