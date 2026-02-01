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
        <transition-group name="fade">
            <notice v-for="(msg, idx) in info"
                :key="'info-'+idx"
                class="alert-info"
                @cleared="removeInfo(idx)"
            >
                {{msg}}
            </notice>
            <notice v-for="(msg, idx) in errors"
                :key="'error-'+idx"
                class="alert-danger"
                :auto-close="false"
                @cleared="removeError(idx)"
            >
                {{msg}}
            </notice>
        </transition-group>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useMessagesStore } from '../stores/messages'
    import notice from './Notice.vue'

    export default {
        components: {
            notice
        },
        computed: {
            ...mapState(useMessagesStore, {
                info: state => state.info,
                errors: state => state.errors
            }),
        },
        methods: {
            ...mapActions(useMessagesStore, [
                'addInfo',
                'addError',
                'removeInfo',
                'removeError'
            ])
        },
    }
</script>
