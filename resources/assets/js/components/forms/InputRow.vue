<template>
    <div :class="{'border border-red-500 rounded-sm p-2 mb-2': hasErrors}">
        <input-errors :errors="errors"></input-errors>
        <div class="my-3" :class="{'d-flex': !vertical}">
            <div :class="{'w-25': !vertical, 'my-1': vertical}">
                <slot name="label" v-if="label">
                    <label :class="{'text-red-800': hasErrors}">{{label}}{{colon}}</label>
                </slot>
            </div>
            <slot>
                <date-input 
                    v-if="type == 'date'"
                    :value="value" 
                    @input="emitValue" 
                ></date-input>
                <input 
                    v-else
                    :type="type" 
                    :value="value" 
                    @input="$emit('input', $event.target.value)"
                    :placeholder="placeholder"
                >
            </slot>
        </div>
    </div>
</template>
<script>
import InputErrors from './InputErrors'
import DateInput from './DateInput'

export default {
    components: {
        InputErrors,
        DateInput
    },
    props: {
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
        value: {
            required: false,
            default: null
        },
        placeholder: {
            required: false,
            value: null
        }
    },
    emits: [
        'update:modelValue'
    ],
    computed: {
        colon () {
            if (this.label && [':',';','.','?', '!'].includes(this.label.substr(-1))) {
                return '';
            }
            return ':';    
        },
        hasErrors () {
            return this.errors.length > 0;
        },
    },
    methods: {
        emitValue(evt) {
            console.log(evt);
            this.$emit('input', evt)
        }
    }
}
</script>