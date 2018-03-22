<style>
    .alerts-container {
        position:fixed; 
        top: 70px; 
        right: 1em;
        min-width: 300px;
    }
</style>
<template>
    <div class="alerts-container">
        <div class="alert alert-info" v-for="(msg, idx) in info">
            <a class="float-right crsr-pointer" @click="removeInfo(idx)">x</a>
            {{msg}}
        </div>
        <div class="alert alert-danger" v-for="(msg, idx) in errors">
            <a class="float-right crsr-pointer" @click="removeError(idx)">x</a>
            {{msg}}
        </div>
    </div>
</template>
<script>
    import {mapState, mapMutations} from 'vuex';

    export default {
        computed: {
            ...mapState('messages', {
                info: state => state.info,
                errors: state => state.errors
            }),
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addError',
                'removeInfo',
                'removeError'
            ])
        },
    }
</script>