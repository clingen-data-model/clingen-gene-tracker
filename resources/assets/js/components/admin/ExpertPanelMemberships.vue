<template>
  <fieldset class="mt-3" :disabled="disabled">
    <legend class="h6">Expert Panels</legend>

    <div
        v-for="(row, index) in modelValue"
        :key="row.key"
        class="border rounded p-2 mb-2"
    >
      <div class="d-flex align-items-center flex-wrap gap-3">
        <div class="expert-panel-select flex-grow-1">
          <SearchSelect
            :input-id="`${id}-${row.key}`"
            :aria-label="`Expert Panel ${index + 1}`"
            :model-value="row.panel"
            :options="availablePanels(row)"
            :search-function="searchPanels"
            :disabled="disabled"
            placeholder="Search Expert Panels by name"
            @update:model-value="update(index, { panel: $event })"
          >
            <template #selection-label="{ selection }">{{ selection.name }}</template>
            <template #option="{ option }">{{ option.name }}</template>
          </SearchSelect>
        </div>

        <label
          v-for="flag in flags"
          :key="flag.key"
          class="form-check-label text-nowrap"
        >
          <input
            type="checkbox"
            class="form-check-input me-1"
            :checked="row[flag.key]"
            @change="update(index, { [flag.key]: $event.target.checked })"
          >
          {{ flag.label }}
        </label>

        <button
          type="button"
          class="btn btn-sm btn-outline-danger"
          :aria-label="`Remove Expert Panel ${index + 1}`"
          @click="emit('update:modelValue', modelValue.filter((_, rowIndex) => rowIndex !== index))"
        >
          Remove
        </button>
      </div>
    </div>

    <button type="button" class="btn btn-outline-primary" @click="add">
      Add Expert Panel
    </button>
  </fieldset>
</template>

<script setup>
import { useId } from 'vue'
import SearchSelect from '../forms/SearchSelect.vue'
const props = defineProps({ modelValue: { type: Array, required: true }, panels: { type: Array, default: () => [] }, disabled: Boolean })
const emit = defineEmits(['update:modelValue'])
const id = useId()
let nextKey = 0
const flags = [
    { key: 'is_curator', label: 'Curator' },
    { key: 'is_coordinator', label: 'Coordinator' },
    { key: 'can_edit_curations', label: 'Edit Curations' },
]
function add() {
    emit('update:modelValue', [...props.modelValue, { key: `new-${nextKey++}`, panel: null, is_curator: false, is_coordinator: false, can_edit_curations: false }])
}
function update(index, changes) {
    emit('update:modelValue', props.modelValue.map((row, rowIndex) => rowIndex === index ? { ...row, ...changes } : row))
}
function availablePanels(row) {
    return props.panels.filter(panel => !props.modelValue.some(other => other !== row && other.panel?.id === panel.id))
}
function searchPanels(value, options) {
    const query = value.trim().toLowerCase()
    return query ? options.filter(panel => panel.name.toLowerCase().includes(query)) : []
}
</script>
<style scoped>
.expert-panel-select {
    min-width: 300px;
}
</style>