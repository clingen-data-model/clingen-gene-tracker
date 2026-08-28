<template>
    <div>
        <documents-card :curation="curation"></documents-card>
    </div>
</template>
<script setup>
import { ref, watch } from 'vue'
import useCurationForm from '../../../composables/useCurationForm'
import DocumentsCard from '../Documents/DocumentsCard.vue'

const props = defineProps(['modelValue', 'errors'])
const emit = defineEmits(['update:modelValue'])
const page = 'documents'
const curation = ref({})
const { updatedCuration } = useCurationForm(props, emit, page)

watch(updatedCuration, (value) => {
    curation.value = value || {}
})
</script>
