<style></style>

<template>
    <transition name="fade">
        <div class="alert alert-info" v-show="omimLoading">
            Loading data from OMIM...
        </div>
    </transition>
</template>

<script>
    import { useAppStore } from '../stores/app'

    export default {
        computed: {
            omimLoading() {
                const appStore = useAppStore()
                return appStore.omimLoading
            }
        }
    }
</script>
