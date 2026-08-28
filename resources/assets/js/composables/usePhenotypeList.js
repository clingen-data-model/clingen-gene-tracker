import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import OmimRepo from '../repositories/OmimRepository'

export default function usePhenotypeList() {
    const store = useStore()
    const phenotypes = ref([])
    const phenotypesLoaded = ref(false)
    const loading = computed(() => store.getters.loading)

    function fetchPhenotypes(curationId) {
        if (curationId) {
            return OmimRepo.forCuration(curationId)
                .then(response => {
                    phenotypes.value = response.data.phenotypes
                    phenotypesLoaded.value = true
                })
                .catch(error => {
                    console.error(error)
                })
        }

        return Promise.resolve()
    }

    return {
        phenotypes,
        phenotypesLoaded,
        loading,
        fetchPhenotypes
    }
}
