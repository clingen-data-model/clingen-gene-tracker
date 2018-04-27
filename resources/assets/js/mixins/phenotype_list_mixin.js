import OmimRepo from '../repositories/OmimRepository';

export default {
    data() {
        return {
            phenotypes: [],
        }
    },
    computed: {
        loading: function () {
            return this.$store.getters.loading;
        }
    },
    methods: {
        fetchPhenotypes: function (geneSymbol) {
            if (geneSymbol) {
                return OmimRepo.gene(geneSymbol)
                    .then( response => this.phenotypes = response.data.phenotypes )
                    .catch( error => alert(error) )
            }
            return new Promise((resolve, reject) => { resolve() })
        },
    },
}