<style></style>

<template>
    <div class="alert">
        <a class="float-end crsr-pointer" @click="$emit('cleared')">x</a>
        <slot></slot>
    </div>
</template>

<script>
export default {
    props: {
        autoClose: {
            type: Boolean,
            default: true
        },
        duration: {
            type: Number,
            default: 3000
        }
    },
    emits: ['cleared'],
    data() {
        return {
            timer: null,
        }
    },
    methods: {
        setTimer() {
            this.timer = setTimeout(() => {
                this.$emit('cleared')
            }, this.duration)
        }
    },
    mounted() {
        if (this.autoClose) {
            this.setTimer();
        }
    }
}
</script>
