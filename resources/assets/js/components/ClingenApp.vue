<style></style>
<template>
    <div class="clingen-app-container container">
        <router-view></router-view>
    </div>
</template>
<script>
    import { mapActions } from 'pinia'
    import { useCurationStatusesStore } from '../stores/curationStatuses'
    import { useRationalesStore } from '../stores/rationales'
    import { useAppStore } from '../stores/app'
    import { useCurationsStore } from '../stores/curations'

    export default {
        methods: {
            ...mapActions(useCurationStatusesStore, {
                getAllCurationStatuses: 'getAllItems'
            }),
            ...mapActions(useRationalesStore, {
                getAllRationales: 'getAllItems'
            }),
            ...mapActions(useAppStore, ['getFeatures']),
        },
        mounted: function () {
            const curationsStore = useCurationsStore()
            if (curationsStore.items.length == 0) {
                this.getAllCurationStatuses();
                this.getAllRationales();
            }
            const appStore = useAppStore()
            if (
                !appStore.features.transferEnabled
                || !appStore.features.sendToGciEnabled
            ) {
                this.getFeatures();
            }
        }
    }
</script>
