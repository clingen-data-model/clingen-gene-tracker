<style></style>
<template>
    <div class="test">
        <pre><code>{{value.id}}</code></pre>
        <button type="button" @click="incrementId()">{{ newValue.id }}</button>
    </div>
</template>
<script>
    export default {
        props: ['value'],
        data: function () {
            return {
                newValue: {}
            }
        },
        watch: {
            value: function (to, from) {
                if (to != from) {
                    this.syncVal();
                }
            }
        },
        methods: {
            incrementId: function () {
                this.newValue.id++;
                this.$emit('input', this.newValue);
            },
            syncVal: function () {
                this.newValue = JSON.parse(JSON.stringify(this.value));
            }
        },
        mounted: function () {
            this.syncVal();
        }
    }
</script>