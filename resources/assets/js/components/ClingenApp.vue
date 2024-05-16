<style></style>
<template>
    <div class="mt-2">
        <div class="clingen-app-container container">
            <router-view></router-view>
        </div>
        <v-progress-linear
            :indeterminate="true"
            v-show="loading"
            style="position:fixed; top:0; left:0; right:0; border-radius: 0"
            height="5px"
        >
        </v-progress-linear>
        <Alerts></Alerts>
    </div>
</template>
<script>
    import { mapActions } from 'vuex'
    import Alerts from '@/components/Alerts'

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
    computed: {
        loading: function () {
            return this.$store.getters.loading;
        }
    },
    mounted: function () {
        if (this.$store.state.curations.items.length == 0) {
            this.getAllCurationStatuses();
            this.getAllRationales();
        }
        if (!this.$store.state.features.transferEnabled
            || !this.$store.state.features.sendToGciEnabled) {
            this.getFeatures();
        }
    },
    components: { Alerts }
}
</script>