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
<script>
    export default {
        props: ['messages'],
        computed: {
            hasErrors: function () {
                if (typeof this.messages == 'undefined') {
                    return false
                }
                return true
            }
        }
    }
</script>