import Vue from 'vue'
import OmimRepo from '../repositories/OmimRepository'

export default {
    props: ['value', 'errors'],
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
        updatedCuration: function (to, from) {
            console.log('updatedCuration changed');
            this.$emit('input', this.updatedCuration);
            this.checkGeneSymbol();
        },
        value: function () {
            if (this.value != this.updatedCuration) {
                this.syncValue();
            }
        }
    },
    methods: {
        syncValue() {
            if (this.value) {
                this.updatedCuration = JSON.parse(JSON.stringify(this.value));
                this.updatedCuration.page = this.page;
            }
        },
        // checkGeneSymbol() {
        //     if (typeof this.updatedCuration.gene_symbol == 'undefined' || this.updatedCuration.gene_symbol === null) {
        //         this.geneSymbolInvalid = false;
        //     }

        //     OmimRepo.gene(this.updatedCuration.gene_symbol)
        //         .then(response => {
        //             this.geneSymbolInvalid = false;
        //         })
        //         .catch(error => {
        //             if (error.response.status == 404) {
        //                 this.geneSymbolInvalid = true;
        //             }
        //         })
        // }
    },
    mounted() {
        this.syncValue();
    }
}