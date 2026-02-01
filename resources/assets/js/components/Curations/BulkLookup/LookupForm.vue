<template>
    <div>
        <div class="d-flex border">
            <div class="nav flex-column nav-pills me-3 p-2 border-end" role="tablist" aria-orientation="vertical">
                <button class="nav-link" :class="{ active: currentTab === 0 }" @click="currentTab = 0">Manual entry</button>
                <button class="nav-link" :class="{ active: currentTab === 1 }" @click="currentTab = 1">CSV Upload</button>
            </div>
            <div class="p-3 flex-grow-1">
                <div v-show="currentTab === 0">
                    <label for="gene-symbol-input">Gene Symbols:</label>
                    &nbsp;
                    <textarea cols="10" rows="3" id="gene-symbol-input" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" class="form-control" maxlength="1900" placeholder="Comma, space, or new-line separated gene symbols, i.e.: BCRA1, TP53 ABSC"></textarea>
                    <div class="mt-1">
                        <button @click="$emit('update:modelValue', '')" class="btn btn-sm btn-light border">Clear</button>
                        <button @click="$emit('lookup')" class="btn btn-primary btn-sm">Search</button>
                        <button @click="$emit('getCsv')" class="btn btn-primary btn-sm float-end">Get CSV</button>
                    </div>
                </div>
                <div v-show="currentTab === 1">
                    <div>
                        <label for="csv-upload">CSV file: </label>
                        <input
                            type="file"
                            accept="csv"
                            id="csv-upload"
                            @change="processFile($event.target.files)"
                            class="d-block"
                            ref="fileInput"
                        >
                        <div class="text-info text-small">
                            <small>File should contain a single column with gene symbols.</small>
                        </div>
                        <div class="form-check my-2">
                            <input type="checkbox" v-model="hasHeader" class="form-check-input" id="has-header">
                            <label for="has-header" class="form-check-label"> has header row</label>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button @click="$emit('lookup')" class="btn btn-primary btn-sm">Search</button>
                        <button @click="$emit('getCsv')" class="btn btn-primary btn-sm float-end">Get CSV</button>
                    </div>
                </div>
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
            currentTab: 0,
            hasHeader: false,
        }
    },
    watch: {
        currentTab: function (to, from) {
            localStorage.setItem('bulk-upload-form-tab-index', to);
        }
    },
    methods: {
        processFile(files) {
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
                reader.readAsText(files[0]);
            }
        },
    },
    mounted() {
        const storedIndex = localStorage.getItem('bulk-upload-form-tab-index');
        this.currentTab =  storedIndex ? parseInt(storedIndex) : 0;
    }
}
</script>
