import { defineComponent, nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import usePhenotypeList from '../../../resources/assets/js/composables/usePhenotypeList'

const { forCuration } = vi.hoisted(() => ({
    forCuration: vi.fn(),
}))

vi.mock('../../../resources/assets/js/repositories/OmimRepository', () => ({
    default: { forCuration },
}))

const Harness = defineComponent({
    setup(_, { expose }) {
        const phenotypeList = usePhenotypeList()
        expose(phenotypeList)
        return () => null
    },
})

function mountHarness(loading = false) {
    const store = createStore({
        state: { loading },
        getters: {
            loading: state => state.loading,
        },
        mutations: {
            setLoading(state, value) {
                state.loading = value
            },
        },
    })
    const wrapper = mount(Harness, {
        global: { plugins: [store] },
    })
    return { store, wrapper }
}

beforeEach(() => {
    forCuration.mockReset()
})

describe('usePhenotypeList', () => {
    it('does not request OMIM data without a curation ID', async () => {
        const { wrapper } = mountHarness()

        await wrapper.vm.fetchPhenotypes(null)

        expect(forCuration).not.toHaveBeenCalled()
        expect(wrapper.vm.phenotypes).toEqual([])
        expect(wrapper.vm.phenotypesLoaded).toBe(false)
        wrapper.unmount()
    })

    it('loads phenotypes for a valid curation ID', async () => {
        const phenotypes = [
            { phenotypeMimNumber: 123456, phenotype: 'Example phenotype' },
        ]
        forCuration.mockResolvedValue({ data: { phenotypes } })
        const { wrapper } = mountHarness()

        await wrapper.vm.fetchPhenotypes(42)

        expect(forCuration).toHaveBeenCalledOnce()
        expect(forCuration).toHaveBeenCalledWith(42)
        expect(wrapper.vm.phenotypes).toEqual(phenotypes)
        expect(wrapper.vm.phenotypesLoaded).toBe(true)
        wrapper.unmount()
    })

    it('logs failed requests and leaves phenotype state unloaded', async () => {
        const error = new Error('OMIM unavailable')
        forCuration.mockRejectedValue(error)
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
        const { wrapper } = mountHarness()

        await wrapper.vm.fetchPhenotypes(42)

        expect(consoleError).toHaveBeenCalledOnce()
        expect(consoleError).toHaveBeenCalledWith(error)
        expect(wrapper.vm.phenotypes).toEqual([])
        expect(wrapper.vm.phenotypesLoaded).toBe(false)
        wrapper.unmount()
    })

    it('reacts to the Vuex loading getter', async () => {
        const { store, wrapper } = mountHarness(false)

        expect(wrapper.vm.loading).toBe(false)

        store.commit('setLoading', true)
        await nextTick()

        expect(wrapper.vm.loading).toBe(true)
        wrapper.unmount()
    })
})
