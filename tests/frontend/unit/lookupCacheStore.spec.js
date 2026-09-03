import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createStore } from 'vuex'
import classifications from '../../../resources/assets/js/store/modules/classifications'
import rationales from '../../../resources/assets/js/store/modules/rationales'

function makeStore(namespace, module) {
    return createStore({
        modules: {
            [namespace]: {
                ...module,
                state: () => ({ items: [] }),
            },
        },
    })
}

describe('classification lookup cache', () => {
    beforeEach(() => {
        localStorage.clear()
        window.axios = { get: vi.fn() }
    })

    it('hydrates from the correctly spelled cache key without requesting the API', async () => {
        const cached = [{ id: 1, name: 'Definitive' }]
        localStorage.setItem('classifications', JSON.stringify(cached))
        const store = makeStore('classifications', classifications)

        await expect(store.dispatch('classifications/getAllItems')).resolves.toBeUndefined()

        expect(store.state.classifications.items).toEqual(cached)
        expect(window.axios.get).not.toHaveBeenCalled()
        expect(localStorage.getItem('clasifications')).toBeNull()
    })

    it('stores successful API data under the same correctly spelled key', async () => {
        const loaded = [{ id: 2, name: 'Moderate' }]
        window.axios.get.mockResolvedValue({ data: loaded })
        const store = makeStore('classifications', classifications)

        await expect(store.dispatch('classifications/getAllItems')).resolves.toBeUndefined()
        await vi.waitFor(() => expect(store.state.classifications.items).toEqual(loaded))

        expect(window.axios.get).toHaveBeenCalledWith('/api/classifications')
        expect(JSON.parse(localStorage.getItem('classifications'))).toEqual(loaded)
        expect(localStorage.getItem('clasifications')).toBeNull()
    })
})

describe('rationale lookup cache', () => {
    beforeEach(() => {
        localStorage.clear()
        window.axios = { get: vi.fn() }
    })

    it('hydrates from an existing cache without removing it or requesting the API', async () => {
        const cached = [{ id: 3, name: 'Case-level data' }]
        localStorage.setItem('rationales', JSON.stringify(cached))
        const store = makeStore('rationales', rationales)

        await expect(store.dispatch('rationales/getAllItems')).resolves.toBeUndefined()

        expect(store.state.rationales.items).toEqual(cached)
        expect(JSON.parse(localStorage.getItem('rationales'))).toEqual(cached)
        expect(window.axios.get).not.toHaveBeenCalled()
    })

    it('requests and caches the normal API response when no cache exists', async () => {
        const loaded = [{ id: 4, name: 'Segregation' }]
        window.axios.get.mockResolvedValue({ data: loaded })
        const store = makeStore('rationales', rationales)

        await expect(store.dispatch('rationales/getAllItems')).resolves.toBeUndefined()
        await vi.waitFor(() => expect(store.state.rationales.items).toEqual(loaded))

        expect(window.axios.get).toHaveBeenCalledWith('/api/rationales')
        expect(JSON.parse(localStorage.getItem('rationales'))).toEqual(loaded)
    })
})
