<template>
    <external-link :href="`https://curation.clinicalgenome.org/curation-central/${curation.gdm_uuid}`" 
        target="gci"
    >
        <slot>{{ curation.gdm_uuid }}</slot>
    </external-link>
</template>
<script setup>
import ExternalLink from '@/components/ExternalLink.vue'
defineProps({
    curation: {
        type: Object,
        required: true
    }
})
</script>