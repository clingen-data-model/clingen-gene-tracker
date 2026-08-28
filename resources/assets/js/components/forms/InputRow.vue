<template>
    <div>
        <div class="my-3" :class="{'d-flex': !vertical}">
            <div :class="{'w-25': !vertical, 'my-1': vertical}">
                <slot name="label" v-if="label">
                    <label :class="{'text-red-800': hasErrors}">{{label}}{{colon}}</label>
                </slot>
            </div>
            <div>
                <slot>
                    <date-input 
                        v-if="type == 'date'"
                        :model-value="modelValue" 
                        @update:model-value="emitValue" 
                    ></date-input>
                    <input 
                        v-else
                        :type="type" 
                        :value="modelValue" 
                        @input="$emit('update:modelValue', $event.target.value)"
                        :placeholder="placeholder"
                    >
                </slot>
                <input-errors :errors="errors"></input-errors>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import InputErrors from './InputErrors.vue'
import DateInput from './DateInput.vue'

const props = defineProps({
    vertical: {
        type: Boolean,
        default: false
    },
    errors: {
        type: Array,
        required: false,
        default: () => []
    },
    label: {
        type: String,
        required: false
    },
    type: {
        type: String,
        required: false,
        default: 'text'
    },
    modelValue: {
        required: false,
        default: null
    },
    placeholder: {
        required: false,
        value: null
    }
})

const emit = defineEmits(['update:modelValue'])

const colon = computed(() => {
    if (props.label && [':',';','.','?', '!'].includes(props.label.substr(-1))) {
        return ''
    }
    return ':'
})

const hasErrors = computed(() => props.errors.length > 0)

function emitValue(evt) {
    console.log(evt)
    emit('update:modelValue', evt)
}
</script>
