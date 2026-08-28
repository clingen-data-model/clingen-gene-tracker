<style>
    .alerts-container {
        position:fixed; 
        top: 70px; 
        right: 1em;
        min-width: 300px;
    }
</style>
<template>
    <div class="alerts-container">
        <transition-group name="fade">
            <notice v-for="(msg, idx) in info"
                :key="'info-' + msg"
                class="alert-info"
                v-on:cleared="removeInfo(idx)"
            >
                {{msg}}
            </notice>
            <notice v-for="(msg, idx) in errors"
                :key="'error-' + msg + '-' + idx"
                class="alert-danger"
                :auto-close="false"
                v-on:cleared="removeError(idx)"
            >
                {{msg}}
            </notice>
        </transition-group>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import notice from './Notice.vue'

const store = useStore()
const info = computed(() => store.state.messages.info)
const errors = computed(() => store.state.messages.errors)

function removeInfo(idx) {
    store.commit('messages/removeInfo', idx)
}

function removeError(idx) {
    store.commit('messages/removeError', idx)
}
</script>
