<template>
    <div>
        <b-tabs vertical pills card class="border"
            v-model="numericCurrentTab"
        >
            <b-tab title="Manual entry">
                <label for="gene-symbol-input">Gene Symbols:</label>
                &nbsp;
                <textarea cols="10" rows="3" id="gene-symbol-input" :value="value" @input="$emit('input', $event.target.value)" class="form-control" maxlength="1900" placeholder="Comma, space, or new-line separated gene symboels, i.e.: BCRA1, TP53 ABSC"></textarea>
                <div class="mt-1">
                    <button @click="$emit('input', '')" class="btn btn-sm btn-light border">Clear</button>
                    <button @click="$emit('lookup')" class="btn btn-primary btn-sm">Search</button>
                    <button @click="$emit('getCsv')" class="btn btn-primary btn-sm float-right">Get CSV</button>
                </div>
            </b-tab>
            <b-tab title="CSV Upload">
                <div>
                    <label for="csv-upload">CSV file: </label>
                    <input 
                        type="file" 
                        accept="csv"
                        id="csv-upload"
                        @change="processFile($event.target.files)"
                        class="d-block" 
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
                    <button @click="$emit('getCsv')" class="btn btn-primary btn-sm float-right">Get CSV</button>
                </div>
            </b-tab>
        </b-tabs>
    </div>
</template>
<script>
    import ValidationError from '../../ValidationError.vue'

export default {
    props: [
        'value',
        'errors'
    ],
    components: {
        ValidationError
    },
    data() {
        return {
            currentTab: 0,
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
        currentTab: function (to, from) {
            localStorage.setItem('bulk-upload-form-tab-index', to);
        }
    },
    methods: {
        processFile(files) {
            if (files.length > 0 && files[0].type == 'text/csv') {
                const reader = new FileReader();
                reader.addEventListener('load', (event) => {
                    let text = event.target.result;
                    if (this.hasHeader) {
                        let genes = text.split("\n");
                        const header = genes.splice(0, 1);    
                        this.$emit('input', genes.join(','));
                        return;
                    }
                    this.$emit('input', text);
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
        const storedIndex = localStorage.getItem('bulk-upload-form-tab-index');
        this.currentTab =  storedIndex ? storedIndex : 0;
    }
}
</script>