<style></style>
<template>
    <transition name="fade">
        <div class="text-danger mt-1" v-show="hasErrors">
            <div v-for="(msg, idx) in messages" :key="idx">
                <small>{{msg}}</small>
            </div>
        </div>
    </transition>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps(['messages'])

const hasErrors = computed(() => {
    if (typeof props.messages == 'undefined') {
        return false
    }

    return true
})
</script>
