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
        <transition-group name="fade" tag="div">
            <notice v-for="(msg, idx) in info"
                :key="'info-'+idx"
                class="alert-info"
                v-on:cleared="removeInfo(idx)"
            >{{msg}}</notice>
        </transition-group>
        <transition-group name="fade" tag="div">
            <notice v-for="(msg, idx) in errors"
                :key="'error-'+idx"
                class="alert-danger"
                :auto-close="false"
                v-on:cleared="removeError(idx)"
            >{{msg}}</notice>
        </transition-group>
    </div>
</template>
<script>
    import {mapState, mapMutations} from 'vuex';
    import notice from './Notice.vue'

    export default {
        components: {
            notice
        },
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