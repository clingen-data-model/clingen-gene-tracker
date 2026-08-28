<style></style>

<template>
    <div class="alert">
        <a class="float-end crsr-pointer" @click="$emit('cleared')">x</a>
        <slot></slot>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
    autoClose: {
        type: Boolean,
        default: true
    },
    duration: {
        type: Number,
        default: 3000
    }
})

const emit = defineEmits(['cleared'])
const timer = ref(null)

function setTimer() {
    timer.value = setTimeout(() => {
        emit('cleared')
    }, props.duration)
}

onMounted(() => {
    if (props.autoClose) {
        setTimer()
    }
})

onUnmounted(() => {
    if (timer.value !== null) {
        clearTimeout(timer.value)
    }
})
</script>
