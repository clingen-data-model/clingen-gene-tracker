<style></style>

<template>
    <div class="component-container w-50">
        <classification-history :curation="modelValue"></classification-history>
        <div class="alert alert-secondary">
            Classifications must be added to a curation via the GCI.
        </div>

    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import useCurationForm from '../../../composables/useCurationForm'
import CurationNotifications from './ExistingCurationNotification.vue'
import ValidationError from '../../ValidationError.vue'
import ClassificationHistory from '../ClassificationHistory.vue'

const props = defineProps(['modelValue', 'errors'])
const emit = defineEmits(['update:modelValue'])
const store = useStore()
const page = 'mondo'

useCurationForm(props, emit, page)

const classifications = computed(() => store.getters['classifications/Items'])

onMounted(() => {
    store.dispatch('classifications/getAllItems')
})
</script>
