<template>
    <form class="mb-3" role="search" @submit.prevent="emit('search', query.trim())">
        <label :for="inputId" class="form-label">Search</label>
        <div class="d-flex gap-2">
            <input :id="inputId" v-model="query" class="form-control" type="search" :placeholder="placeholder" maxlength="200">
            <button class="btn btn-primary" type="submit">Search</button>
            <button class="btn btn-outline-secondary" type="button" @click="clear">Clear</button>
        </div>
    </form>
</template>

<script setup>
import { ref, useId } from 'vue'
defineProps({ placeholder: { type: String, default: '' } })
const emit = defineEmits(['search'])
const query = ref('')
const inputId = useId()
function clear() { query.value = ''; emit('search', '') }
</script>
