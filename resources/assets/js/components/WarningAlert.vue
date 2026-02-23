<style></style>

<template>
    <div>
        <transition name="fade">
            <div class="alert alert-warning">
                <div class="justify-content-between d-flex">
                    <slot name="summary"></slot>
                    <button type="button" class="btn btn-sm btn-warning" @click="showDetails = !showDetails">
                        Details
                    </button>
                </div>
                <div v-show="showDetails" class="mt-2">
                    <slot name="details"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
    export default {
        components: {},
        data() {
            return {
                showDetails: false
            }
        },
        methods: {}
    }
</script>
