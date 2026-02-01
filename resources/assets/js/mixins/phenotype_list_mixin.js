import OmimRepo from '../repositories/OmimRepository'
import { useAppStore } from '../stores/app'

export default {
    data() {
        return {
            phenotypes: [],
            phenotypesLoaded: false,
        }
    },
    computed: {
        loading: function () {
            const appStore = useAppStore()
            return appStore.loading
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
