<style></style>

<template>
    <div>
        <transition name="fade">
            <div class="alert alert-warning">
                <div class="justify-content-between d-flex">
                    <slot name="summary"></slot>
                    <button type="button" class="btn btn-sm btn-warning" v-b-toggle.matching-curations-details>
                        Details
                    </button>
                </div>
                <b-collapse id="matching-curations-details" class="mt-2">
                    <slot name="details"></slot>
                </b-collapse>
            </div>
        </transition>
    </div>
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