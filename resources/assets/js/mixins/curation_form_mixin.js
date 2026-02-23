import OmimRepo from '../repositories/OmimRepository'

export default {
    props: ['modelValue', 'errors'],
    data() {
        return {
            updatedCuration: {
                gene_symbol: null,
                ratonionales: []
            },
            // geneSymbolInvalid: false,
            page: null
        }
    },
    watch: {
        updatedCuration: function(to, from) {
            this.$emit('update:modelValue', this.updatedCuration);
        },
        modelValue: function() {
            if (this.modelValue != this.updatedCuration) {
                this.syncValue();
            }
        }
    },
    methods: {
        syncValue() {
            if (this.modelValue) {
                this.updatedCuration = JSON.parse(JSON.stringify(this.modelValue));
                this.updatedCuration.page = this.page;
            }
        },
    },
    mounted() {
        this.syncValue();
    }
}
