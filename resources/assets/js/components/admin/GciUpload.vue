<template>
    <form class="border rounded p-3 mb-3" @submit.prevent="submit">
        <h3 class="h5">Upload GCI JSON</h3>
        <label class="form-label" for="gci-source">File type</label>
        <select id="gci-source" v-model="source" class="form-select mb-2" :disabled="disabled">
            <option value="HAS_PRECURATION_ID">GDM with Precuration ID</option>
            <option value="NO_PRECURATION_ID">GDM without Precuration ID</option>
        </select>
        <label class="form-label" for="gci-file">JSON file</label>
        <input id="gci-file" class="form-control mb-2" type="file" accept=".json,application/json" required :disabled="disabled" @change="file = $event.target.files[0]" />
        <button class="btn btn-primary" :disabled="disabled || !file">Upload to selected run</button>
    </form>
</template>
<script setup>
import { ref } from 'vue'
defineProps({ disabled: Boolean })
const emit = defineEmits(['upload'])
const source = ref('HAS_PRECURATION_ID')
const file = ref(null)
function submit() {
    const data = new FormData()
    data.append('source_type', source.value)
    data.append('file', file.value)
    emit('upload', data)
}
</script>
