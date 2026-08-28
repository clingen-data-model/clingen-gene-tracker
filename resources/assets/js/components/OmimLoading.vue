<style></style>

<template>
    <transition name="fade">
        <div class="alert alert-info" v-show="omimLoading">
            Loading data from OMIM...
        </div>
    </transition>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const omimLoading = computed(() => store.getters.omimLoading)
</script>
