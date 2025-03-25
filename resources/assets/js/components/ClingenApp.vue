<style></style>
<template>
    <div class="mt-2">
        <div class="clingen-app-container container">
            <router-view></router-view>
        </div>
        <b-progress 
            :value="100" 
            :max="100"  
            animated 
            v-show="loading"
            style="position:fixed; top:0; left:0; right:0; border-radius: 0"
            height="5px"
        >
        </b-progress>
        <Alerts></Alerts>
    </div>
</template>
<script>
    import { mapActions } from 'vuex'
    import Alerts from '@/components/Alerts.vue'

    export default {
        methods: {
            ...mapActions('curationStatuses', {
                getAllCurationStatuses: 'getAllItems'
            }),
            ...mapActions('rationales', {
                getAllRationales: 'getAllItems'
            }),
            ...mapActions({
                getFeatures: 'getFeatures'
            })
        },
        components: {
            Alerts,
        },
        data() {
            return {
                loading: true
            }
        },
        mounted: function () {
            if (this.$store.state.curations.items.length == 0) {
                this.getAllCurationStatuses();
                this.getAllRationales();
            }
            if (
                !this.$store.state.features.transferEnabled
                || !this.$store.state.features.sendToGciEnabled
            ) {
                this.getFeatures();
            }
            this.loading = false;
        }
    }
</script>