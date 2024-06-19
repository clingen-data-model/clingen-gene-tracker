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
        fetchPhenotypes: function (curationId) {
            if (curationId) {
                return OmimRepo.forCuration(curationId)
                    .then( response => {
                        this.phenotypes = response.data.phenotypes;
                        this.phenotypesLoaded = true
                    })
                    .catch( error => {
                        console.error(error) 
                    })
            }
            return new Promise((resolve, reject) => { resolve() })
        },
    },
}