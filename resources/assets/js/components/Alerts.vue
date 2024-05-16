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
        <transition name="fade">
            <div>
                <notice v-for="(msg, idx) in info"
                    v-bind:key="idx"
                    class="alert-info"
                    v-on:cleared="removeInfo(idx)"
                >
                    {{msg}}
                </notice>
                <notice v-for="(msg, idx) in errors"
                    v-bind:key="idx"
                    class="alert-danger"
                    :auto-close="false"
                    v-on:cleared="removeError(idx)"
                >
                    {{msg}}
                </notice>
            </div>
        </transition>
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