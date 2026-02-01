<template>
    <button class="btn btn-sm">
        <small>
            <small @click="toggle">
                {{otherStateLabel}}
            </small>
        </small>
    </button>
</template>
<script>
export default {
    props: {
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
    },
    emits: ['update:modelValue'],
    computed: {
        otherStateLabel () {
            return this.modelValue ? this.hideLabel : this.showLabel;
        }
    },
    methods: {
        toggle () {
            this.$emit('update:modelValue', !this.modelValue);
        }
    }
}
</script>
