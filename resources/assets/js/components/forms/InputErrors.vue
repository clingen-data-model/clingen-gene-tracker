<template>
    <ul class="text-danger list-unstyled pt-0 mt-0">
        <li v-for="error in errors" :key="error">{{error}}</li>
    </ul>
</template>
<script setup>
defineProps({
    errors: {
        type: Array,
        required: true
    }
})
</script>
