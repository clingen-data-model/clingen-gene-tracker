<template>
    <ul class="text-red-800 m-0 p-0">
        <li v-for="error in errors" :key="error">{{error}}</li>
    </ul>
</template>
<script>
export default {
    props: {
        errors: {
            type: Array,
            required: true 
        }
    }
}
</script>