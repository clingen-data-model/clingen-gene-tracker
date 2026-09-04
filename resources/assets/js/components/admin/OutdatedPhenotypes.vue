<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Outdated Phenotype Labels</h2>
            <b-button variant="outline-primary" :disabled="downloading" @click="downloadCsv">
                <b-spinner v-if="downloading" small aria-label="Downloading" />
                Download CSV
            </b-button>
        </div>
        <b-alert v-if="errorMessage" variant="danger" show>{{ errorMessage }}</b-alert>

        <div class="btn-group mb-3" role="group" aria-label="Outdated phenotype report views">
            <router-link
                class="btn"
                :class="tab === 'phenotypes' ? 'btn-primary' : 'btn-outline-primary'"
                :to="{ name: 'admin-outdated-phenotypes', query: { tab: 'phenotypes' } }"
            >Outdated Phenotype Labels</router-link>
            <router-link
                class="btn"
                :class="tab === 'curations' ? 'btn-primary' : 'btn-outline-primary'"
                :to="{ name: 'admin-outdated-phenotypes', query: { tab: 'curations' } }"
            >Affected Curations</router-link>
        </div>

        <b-table :items="items" :fields="fields" :busy="loading" responsive striped show-empty empty-text="No matching records.">
            <template #table-busy><div class="text-center my-3">Loading report...</div></template>
            <template #cell(affected_curations)="{ item }">
                <span v-if="!item.affected_curations">N/A</span>
                <span v-else>
                    {{ item.affected_curations }} Curation(s):
                    <template v-for="(curation, index) in item.curations" :key="curation.id">
                        <router-link :to="{ name: 'curations-show', params: { id: curation.id } }">
                            {{ curation.gene_symbol || curation.id }} #{{ curation.id }}
                        </router-link><span v-if="index < item.curations.length - 1">, </span>
                    </template>
                </span>
            </template>
            <template #cell(id)="{ item }">
                <router-link :to="{ name: 'curations-show', params: { id: item.id } }">{{ item.id }}</router-link>
            </template>
            <template #cell(mondo)="{ item }">{{ item.mondo_id }}<span v-if="item.mondo_name"> — {{ item.mondo_name }}</span></template>
            <template #cell(expert_panel)="{ item }">{{ item.expert_panel?.name || '—' }}</template>
            <template #cell(phenotypes)="{ item }">{{ phenotypeLabels(item.phenotypes) }}</template>
        </b-table>

        <b-pagination
            v-if="total > perPage"
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            aria-label="Report pagination"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const tab = computed(() => route.query.tab === 'curations' ? 'curations' : 'phenotypes')
const items = ref([])
const loading = ref(false)
const downloading = ref(false)
const errorMessage = ref('')
const currentPage = ref(1)
const perPage = 20
const total = ref(0)
const fields = computed(() => tab.value === 'phenotypes'
    ? [
        { key: 'mim_number', label: 'MIM' },
        { key: 'name', label: 'Name' },
        { key: 'affected_curations', label: 'Affected Curations' },
    ]
    : [
        { key: 'id', label: 'Precuration ID' },
        { key: 'gene_symbol', label: 'Gene' },
        { key: 'mondo', label: 'MONDO / Disease' },
        { key: 'expert_panel', label: 'Expert Panel' },
        { key: 'phenotypes', label: 'Outdated Phenotype Labels' },
    ])

function phenotypeLabels(phenotypes) {
    return phenotypes?.map(phenotype => `${phenotype.name} (${phenotype.mim_number})`).join('; ') || '—'
}

async function loadReport() {
    loading.value = true
    errorMessage.value = ''
    const endpoint = tab.value === 'curations'
        ? '/api/admin/reports/outdated-curations'
        : '/api/admin/reports/outdated-phenotypes'
    try {
        const response = await window.axios.get(endpoint, { params: { page: currentPage.value, per_page: perPage } })
        items.value = response.data.data
        total.value = response.data.total
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to load the outdated phenotype report.'
    } finally {
        loading.value = false
    }
}

async function downloadCsv() {
    downloading.value = true
    errorMessage.value = ''
    const endpoint = `/api/admin/reports/outdated-${tab.value}/export`
    try {
        const response = await window.axios.get(endpoint, { responseType: 'blob' })
        const url = URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url
        link.download = `omim_outdated_phenotype_labels_${tab.value}.csv`
        link.click()
        URL.revokeObjectURL(url)
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to download the report.'
    } finally {
        downloading.value = false
    }
}

watch(tab, async () => {
    currentPage.value = 1
    await loadReport()
}, { immediate: true })
watch(currentPage, loadReport)
</script>
