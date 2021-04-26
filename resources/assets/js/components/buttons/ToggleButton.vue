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
        value: {
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
    data() {
        return {
            
        }
    },
    computed: {
        otherStateLabel () {
            return this.value ? this.hideLabel : this.showLabel;
        }
    },
    methods: {
        toggle () {
            this.$emit('input', !this.value);
        }
    }
}
</script>