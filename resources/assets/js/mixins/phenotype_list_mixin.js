import OmimRepo from '../repositories/OmimRepository';

export default {
    data() {
        return {
            phenotypes: [],
            phenotypesLoaded: false,
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
                    .then( response => {
                        this.phenotypes = response.data.phenotypes;
                        this.phenotypesLoaded = true
                    })
                    .catch( error => {
                        alert(error) 
                    })
            }
            return new Promise((resolve, reject) => { resolve() })
        },
    },
}