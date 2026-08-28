<style></style>

<template>
    <div class="form-group d-flex align-items-center">
        <!-- <pre>{{updatedPanel}}</pre> -->
        <select v-model="updatedPanel.expert_panel_id" class="form-control">
            <option>Select...</option>
            <option v-for="option in panelOptions" v-bind:key="option.id" :value="option.id">{{option.name}}</option>
        </select>
        <label>
            <input type="checkbox" v-model="updatedPanel.is_curator">
            Curator
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.is_coordinator">
            Coordinator
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.can_edit_curations">
            Can edit curations
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.can_edit_curations">
            Can create curations
        </label>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

defineProps(['panel', 'panelOptions'])
const emit = defineEmits(['updated'])

const updatedPanel = ref({})

watch(updatedPanel, value => {
    emit('updated', value)
})
</script>
