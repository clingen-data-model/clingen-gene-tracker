<template>
    <div>
        <div class="alert alert-warning">
            <div class="justify-content-between d-flex">
                <slot name="summary"></slot>

                <button
                    type="button"
                    class="btn btn-sm btn-warning"
                    @click="detailsVisible = !detailsVisible"
                >
                    {{ detailsVisible ? 'Hide details' : 'Details' }}
                </button>
            </div>

            <div
                v-show="detailsVisible"
                class="mt-2"
            >
                <slot name="details"></slot>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            detailsVisible: false
        }
    }
}
</script>