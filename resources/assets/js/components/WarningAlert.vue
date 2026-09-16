<template>
    <div>
        <div class="alert alert-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div><slot name="summary"></slot></div>
                <button type="button" class="btn btn-sm btn-warning" @click="detailsVisible = !detailsVisible">
                    {{ detailsVisible ? 'Hide details' : 'Details' }}
                </button>
            </div>

            <div v-show="detailsVisible" class="mt-2" >
                <slot name="details"></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const detailsVisible = ref(false)
</script>
