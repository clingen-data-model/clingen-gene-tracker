import { beforeEach, describe, expect, it, vi } from 'vitest'
import store from '../../../resources/assets/js/store'

describe('root Vuex store', () => {
    beforeEach(() => {
        store.replaceState({
            ...store.state,
            requestCount: 0,
            apiRequestCounts: {
                omim: 0,
                mondo: 0,
                pubmed: 0,
            },
            features: {
                transferEnabled: false,
                sendToGciEnabled: false,
            },
        })
        window.axios = {
            get: vi.fn(),
        }
    })

    it('tracks requests and derives the loading state', () => {
        expect(store.getters.loading).toBe(false)

        store.commit('addRequest')
        expect(store.state.requestCount).toBe(1)
        expect(store.getters.loading).toBe(true)

        store.commit('removeRequest')
        expect(store.state.requestCount).toBe(0)
        expect(store.getters.loading).toBe(false)
    })

    it('keeps requestCount at zero when an unmatched decrement occurs', () => {
        store.commit('removeRequest')

        expect(store.state.requestCount).toBe(0)
        expect(store.getters.loading).toBe(false)
    })

    it('tracks supported API requests and the OMIM loading getter', () => {
        store.commit('addApiRequest', 'omim')
        store.commit('addApiRequest', 'mondo')

        expect(store.state.apiRequestCounts).toEqual({ omim: 1, mondo: 1, pubmed: 0 })
        expect(store.getters.omimLoading).toBe(true)

        store.commit('removeApiRequest', 'omim')
        store.commit('removeApiRequest', 'mondo')
        expect(store.state.apiRequestCounts).toEqual({ omim: 0, mondo: 0, pubmed: 0 })
        expect(store.getters.omimLoading).toBe(false)
    })

    it('keeps API request counters at zero when an unmatched decrement occurs', () => {
        store.commit('removeApiRequest', 'pubmed')

        expect(store.state.apiRequestCounts.pubmed).toBe(0)
    })

    it('rejects unknown API counter keys and ignores object payloads', () => {
        expect(() => store.commit('addApiRequest', 'unknown')).toThrow(
            'unknown is not a valid key for apiRequestCounts.'
        )

        store.commit('addApiRequest', { apiKey: 'omim' })
        expect(store.state.apiRequestCounts.omim).toBe(0)
    })

    it('loads and commits feature flags from the existing endpoint', async () => {
        const features = { transferEnabled: true, sendToGciEnabled: true }
        window.axios.get.mockResolvedValue({ data: features })

        await store.dispatch('getFeatures')

        expect(window.axios.get).toHaveBeenCalledWith('/api/features')
        expect(store.state.features).toEqual(features)
    })

    it.each([
        {
            name: 'default feature state',
            features: { transferEnabled: false, sendToGciEnabled: false },
        },
        {
            name: 'previously loaded feature state',
            features: { transferEnabled: true, sendToGciEnabled: false },
        },
    ])('logs a feature-load failure and preserves $name', async ({ features }) => {
        const error = { response: { status: 503 } }
        store.commit('setFeatures', features)
        window.axios.get.mockRejectedValue(error)
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})

        await store.dispatch('getFeatures')

        expect(consoleError).toHaveBeenCalledWith(error.response)
        expect(store.state.features).toEqual(features)
    })
})
