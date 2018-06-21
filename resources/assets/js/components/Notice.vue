<style></style>

<template>
    <div class="alert">
        <a class="float-right crsr-pointer" @click="$emit('cleared')">x</a>
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
    data() {
        return {
            timer: null,
        }
    },
    methods: {
        setTimer() {
            console.log('start timer...');
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