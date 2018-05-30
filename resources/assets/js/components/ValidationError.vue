<style></style>
<template>
    <div class="text-danger mt-1" v-show="hasErrors">
        <div v-for="msg in messages"><small>{{msg}}</small></div>
    </div>
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