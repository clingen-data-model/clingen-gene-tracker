<template>
  <b-tabs vertical pills card class="border lookup-form" v-model:index="numericCurrentTab" nav-wrapper-class="align-self-stretch" content-class="lookup-form-content">
    <b-tab title="Manual entry" title-link-class="text-start">
      <label for="gene-symbol-input">Gene Symbols:</label>
      &nbsp;
      <textarea
        id="gene-symbol-input"
        cols="10"
        rows="3"
        :value="modelValue"
        class="form-control"
        maxlength="1900"
        placeholder="Comma, space, or new-line separated gene symbols, i.e.: BRCA1, TP53 ABCD"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <div class="mt-1 d-flex flex-wrap gap-2">
        <button
          type="button"
          class="btn btn-sm btn-light border"
          @click="$emit('update:modelValue', '')"
        >
          Clear
        </button>

        <button
          type="button"
          class="btn btn-primary btn-sm"
          @click="$emit('lookup')"
        >
          Search
        </button>

        <button
          type="button"
          class="btn btn-primary btn-sm ms-auto"
          @click="$emit('getCsv')"
        >
          Get CSV
        </button>
      </div>
    </b-tab>

    <b-tab title="CSV Upload" title-link-class="text-start">
      <div>
        <label for="csv-upload">CSV file: </label>
        <input
          id="csv-upload"
          ref="fileInput"
          type="file"
          accept=".csv,text/csv"
          class="d-block"
          @change="processFile($event.target.files)"
        >

        <div class="text-info text-small">
          <small>File should contain a single column with gene symbols.</small>
        </div>

        <div class="form-check my-2">
          <input
            id="has-header"
            v-model="hasHeader"
            type="checkbox"
            class="form-check-input"
          >
          <label for="has-header" class="form-check-label"> has header row</label>
        </div>
      </div>

      <div class="mt-2 d-flex flex-wrap gap-2">
        <button
          type="button"
          class="btn btn-primary btn-sm"
          @click="$emit('lookup')"
        >
          Search
        </button>

        <button
          type="button"
          class="btn btn-primary btn-sm ms-auto"
          @click="$emit('getCsv')"
        >
          Get CSV
        </button>
      </div>
    </b-tab>
  </b-tabs>
</template>
<script setup>
import { computed, onMounted, ref, watch } from 'vue'

defineProps(['modelValue', 'errors'])

const emit = defineEmits(['update:modelValue', 'lookup', 'getCsv'])
const currentTab = ref('manual')
const hasHeader = ref(false)
const fileInput = ref(null)

const numericCurrentTab = computed({
  get() {
    return currentTab.value === 'csv' ? 1 : 0
  },
  set(value) {
    currentTab.value = value === 1 ? 'csv' : 'manual'
  }
})

watch(currentTab, (to) => {
  localStorage.setItem('bulk-upload-form-tab', to)
})

function processFile(files) {
  console.log(files)
  if (files[0].type !== 'text/csv') {
    alert('The file must be a csv.')
    fileInput.value.value = null
    return
  }
  if (files.length > 0 && files[0].type == 'text/csv') {
    const reader = new FileReader()
    reader.addEventListener('load', (event) => {
        let text = event.target.result
        if (hasHeader.value) {
          let genes = text.split("\n")
          const header = genes.splice(0, 1)
          emit('update:modelValue', genes.join(','))
          return
        }
        emit('update:modelValue', text)
    })
    reader.addEventListener('progress', (event) => {
      if (event.loaded && event.total) {
        const percent = (event.loaded / event.total) * 100
        console.log(`progress: ${Math.round(percent)}`)
      }
    })
    reader.readAsText(files[0])
  }
}

onMounted(() => {
  const storedTab = localStorage.getItem('bulk-upload-form-tab')
  currentTab.value = ['manual', 'csv'].includes(storedTab) ? storedTab : 'manual'
})
</script>

<style scoped>
:deep(.lookup-form-content) {
  flex: 1;
  min-width: 0;
}
@media (max-width: 575.98px) {
  .lookup-form {
    flex-direction: column;
  }
  :deep(.lookup-form-content) {
    width: 100%;
  }
}
</style>
