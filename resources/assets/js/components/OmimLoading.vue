<style></style>

<template>
    <transition name="fade">
        <div class="alert alert-info" v-show="$store.getters.omimLoading">
            Loading data from OMIM...
        </div>
    </transition>
</template>

<script>
    export default {
        components: {},
        data() {
            return {}
        },
        methods: {}
    
}
</script>