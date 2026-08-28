<style></style>
<template>
    <div class="clingen-app-container container">
        <router-view></router-view>
    </div>
</template>
<script setup>
import { onMounted } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

onMounted(() => {
    if (store.state.curations.items.length == 0) {
        store.dispatch('curationStatuses/getAllItems')
        store.dispatch('rationales/getAllItems')
    }
    if (
        !store.state.features.transferEnabled
        || !store.state.features.sendToGciEnabled
    ) {
        store.dispatch('getFeatures')
    }
})
</script>
