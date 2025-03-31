<style></style>
<template>
    <NavBar />
    <div class="mt-2">
        <div class="clingen-app-container container">
            <router-view></router-view>
        </div>
        <ProgressBar mode="indeterminate" v-show="loading" style="height: 6px; position:fixed; top:0; left:0; right:0; border-radius: 0" />
        <Alerts></Alerts>
    </div>
</template>
<script>
    import { mapActions } from 'vuex'
    import Alerts from '@/components/Alerts.vue'
    import ProgressBar from 'primevue/progressbar'
    import NavBar from '@/components/NavBar.vue'

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
            NavBar,
            ProgressBar,
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