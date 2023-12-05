<style scoped>
    .material-icons {
        font-size: inherit;
    }
</style>
<template>
    <a>
        <slot></slot>
        <span class="material-icons">open_in_new</span>
    </a>
</template>
<script>
export default {
}
</script>