<script setup>
import { computed, onMounted, ref } from 'vue'
import { onBeforeRouteUpdate } from 'vue-router'
import { useStore } from 'vuex'
import ExpertPanelTabs from './ExpertPanelTabs.vue'

const props = defineProps(['id'])
const store = useStore()
const loading = ref(false)

const groups = computed(() => store.getters['workingGroups/Items'])
const group = computed(() => {
    if (groups.value.length == 0) {
        return {}
    }
    return store.getters['workingGroups/getItemById'](props.id)
})
const hasPanels = computed(() => {
    return Boolean(group.value && group.value.expert_panels && group.value.expert_panels.length > 0)
})

onBeforeRouteUpdate(to => {
    store.dispatch('workingGroups/fetchItem', to.params.id)
})

onMounted(() => {
    loading.value = true
    store.dispatch('workingGroups/fetchItem', props.id)
        .then(() => {
            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
})
</script>

<template>
  <div>
    <div>
      <router-link to="/working-groups">Working groups</router-link>
      &gt;
      <router-link :to="`/working-groups/${group.id}`">{{group.name}}</router-link>
    </div>
    <div class="Working group detail card">
      <div class="card-header"><h3>{{group.name}}</h3></div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Expert Panels</h4>
          <a v-if="group.id" class="btn btn-outline-primary btn-sm" :href="`/working-groups/${group.id}/export`">Download WG Export</a>
        </div>
        <b-tabs pills card vertical v-show="hasPanels" nav-wrapper-class="w-25">
          <b-tab v-for="panel in group.expert_panels" :key="panel.id" :title="panel.name" lazy>
            <ExpertPanelTabs :expert-panel="panel" />
          </b-tab>
        </b-tabs>
        <div class="alert alert-secondary" v-show="!hasPanels && !loading">
          This working group does not have any expert panels
        </div>
        <div class="alert alert-secondary" v-show="loading">
          Loading &hellip;
        </div>
      </div>
    </div>
  </div>
</template>
