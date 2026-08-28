<template>
    <button class="btn btn-sm">
        <small>
            <small @click="toggle">
                {{otherStateLabel}}
            </small>
        </small>
    </button>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        required: true,
        type: Boolean
    },
    showLabel: {
        required: false,
        default: 'Show'
    },
    hideLabel: {
        required: false,
        default: 'Hide'
    }
})

const emit = defineEmits(['update:modelValue'])

const otherStateLabel = computed(() => {
    return props.modelValue ? props.hideLabel : props.showLabel
})

function toggle() {
    emit('update:modelValue', !props.modelValue)
}
</script>
