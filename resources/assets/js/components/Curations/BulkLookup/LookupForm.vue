<template>
  <div class="border p-3">
    <div class="d-flex mb-3">
      <button
        type="button"
        class="btn btn-sm me-2"
        :class="currentTab === 'manual' ? 'btn-primary' : 'btn-light border'"
        @click="currentTab = 'manual'"
      >
        Manual entry
      </button>

      <button
        type="button"
        class="btn btn-sm"
        :class="currentTab === 'csv' ? 'btn-primary' : 'btn-light border'"
        @click="currentTab = 'csv'"
      >
        CSV Upload
      </button>
    </div>

    <div v-if="currentTab === 'manual'">
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

      <div class="mt-1">
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
          class="btn btn-primary btn-sm float-end"
          @click="$emit('getCsv')"
        >
          Get CSV
        </button>
      </div>
    </div>

    <div v-if="currentTab === 'csv'">
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

      <div class="mt-2">
        <button
          type="button"
          class="btn btn-primary btn-sm"
          @click="$emit('lookup')"
        >
          Search
        </button>

        <button
          type="button"
          class="btn btn-primary btn-sm float-end"
          @click="$emit('getCsv')"
        >
          Get CSV
        </button>
      </div>
    </div>
  </div>
</template>
<script>
import ValidationError from '../../ValidationError.vue'

export default {
  props: [
    'modelValue',
    'errors'
  ],
  emits: ['update:modelValue', 'lookup', 'getCsv'],
  components: {
    ValidationError
  },
  data() {
    return {
      currentTab: 'manual',
      hasHeader: false,
    }
  },
  computed: {
    numericCurrentTab: {
      get: function() {
        return parseInt(this.currentTab);
      },
      set: function (value) {
        this.currentTab = value;
      }
    }
  },
  watch: {
    currentTab: function (to) {
      localStorage.setItem('bulk-upload-form-tab', to);
    }
  },
  methods: {
    processFile(files) {
      console.log(files)
      if (files[0].type !== 'text/csv') {
        alert('The file must be a csv.')
        this.$refs.fileInput.value = null;
        return;
      }
      if (files.length > 0 && files[0].type == 'text/csv') {
        const reader = new FileReader();
        reader.addEventListener('load', (event) => {
            let text = event.target.result;
            if (this.hasHeader) {
              let genes = text.split("\n");
              const header = genes.splice(0, 1);    
              this.$emit('update:modelValue', genes.join(','));
              return;
            }
            this.$emit('update:modelValue', text);
        });
        reader.addEventListener('progress', (event) => {
          if (event.loaded && event.total) {
            const percent = (event.loaded / event.total) * 100;
            console.log(`progress: ${Math.round(percent)}`);
          }
          });
          reader.readAsText(files[0]);
      }
    },
  },
  mounted() {
    const storedTab = localStorage.getItem('bulk-upload-form-tab');
    this.currentTab = ['manual', 'csv'].includes(storedTab) ? storedTab : 'manual';
  }
}
</script>
