<template>
    <div>
        <input 
            type="date" 
            :value="formattedDate" 
            :id="id"
            :min="min"
            :max="max"
            :disabled="disabled"
            :readonly="readonly"
            class="form-control"
            @input="setDate"
        >
    </div>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        required: false,
        default: null
    },
    id: {
        type: String,
        required: false
    },
    min: {
        type: String,
        required: false
    },
    max: {
        type: String,
        required: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const formattedDate = computed(() => {
    if (!props.modelValue) {
        return null
    }

    const fmtdt = formatDate(props.modelValue)
    console.log(fmtdt)
    return fmtdt
})

function setDate(event) {
    const date = new Date(Date.parse(event.target.value))
    const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset()*60*1000)

    emit('update:modelValue', adjustedDate)
}

function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear()

    if (month.length < 2)
        month = '0' + month
    if (day.length < 2)
        day = '0' + day

    return [year, month, day].join('-')
}
</script>
