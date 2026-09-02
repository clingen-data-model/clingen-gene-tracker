import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createStore } from 'vuex'
import curations from '../../../resources/assets/js/store/modules/curations'

function makeStore() {
    return createStore({
        modules: {
            curations: {
                ...curations,
                state: () => ({ items: [], currentItemIdx: null }),
            },
        },
    })
}

function response(data, extra = {}) {
    return { data, status: 200, ...extra }
}

describe('curations Vuex store', () => {
    let store

    beforeEach(() => {
        store = makeStore()
        window.axios = {
            delete: vi.fn(),
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
        }
    })

    it('stores a new curation and returns the Axios response', async () => {
        const payload = { gene_symbol: 'BRCA1' }
        const result = response({ data: { id: 11, ...payload } }, { status: 201 })
        window.axios.post.mockResolvedValue(result)

        await expect(store.dispatch('curations/storeNewItem', payload)).resolves.toBe(result)
        expect(window.axios.post).toHaveBeenCalledWith('/api/curations', payload)
        expect(store.state.curations.items).toEqual([result.data.data])
    })

    it('updates a curation, replacing the stored object, and returns the response', async () => {
        const existing = { id: 12, gene_symbol: 'OLD' }
        const payload = { id: 12, gene_symbol: 'NEW' }
        const result = response({ data: payload })
        store.state.curations.items.push(existing)
        window.axios.put.mockResolvedValue(result)

        await expect(store.dispatch('curations/storeItemUpdates', payload)).resolves.toBe(result)
        expect(window.axios.put).toHaveBeenCalledWith('/api/curations/12', payload)
        expect(store.state.curations.items).toEqual([payload])
    })

    it('passes through write failures for create and update', async () => {
        const createError = new Error('create failed')
        const updateError = new Error('update failed')
        window.axios.post.mockRejectedValueOnce(createError)
        window.axios.put.mockRejectedValueOnce(updateError)

        await expect(store.dispatch('curations/storeNewItem', {})).rejects.toBe(createError)
        await expect(store.dispatch('curations/storeItemUpdates', { id: 13 })).rejects.toBe(updateError)
        expect(store.state.curations.items).toEqual([])
    })

    it('fetches a curation, stores it, selects it, and returns the response', async () => {
        const result = response({ data: { id: 14, gene_symbol: 'TP53' } })
        window.axios.get.mockResolvedValue(result)

        await expect(store.dispatch('curations/fetchItem', 14)).resolves.toBe(result)
        expect(window.axios.get).toHaveBeenCalledWith('/api/curations/14')
        expect(store.state.curations.items).toEqual([result.data.data])
        expect(store.getters['curations/currentItem']).toEqual(result.data.data)
    })

    it('rejects fetch failures with error.response and leaves state unchanged', async () => {
        const error = { response: { status: 404, data: { message: 'missing' } } }
        window.axios.get.mockRejectedValue(error)

        await expect(store.dispatch('curations/fetchItem', 404)).rejects.toBe(error.response)
        expect(store.state.curations.items).toEqual([])
        expect(store.state.curations.currentItemIdx).toBeNull()
    })

    it('links a status only after the response and mutates the supplied curation', async () => {
        const curation = { id: 15, curation_statuses: [] }
        const payload = { status_id: 2 }
        const status = { id: 2, pivot: { id: 151 } }
        const result = response(status)
        let resolveRequest
        window.axios.post.mockReturnValue(new Promise(resolve => { resolveRequest = resolve }))
        vi.spyOn(console, 'log').mockImplementation(() => {})

        const pending = store.dispatch('curations/linkNewStatus', { curation, data: payload })
        expect(curation.curation_statuses).toEqual([])

        resolveRequest(result)
        await expect(pending).resolves.toBe(result)
        expect(window.axios.post).toHaveBeenCalledWith('/api/curations/15/statuses', payload)
        expect(curation.curation_statuses).toEqual([status])
        expect(store.state.curations.items[0]).toEqual(curation)
    })

    it('does not duplicate an already-linked status', async () => {
        const status = { id: 2, pivot: { id: 152 } }
        const curation = { id: 15, curation_statuses: [status] }
        const result = response(status)
        window.axios.post.mockResolvedValue(result)
        vi.spyOn(console, 'log').mockImplementation(() => {})

        await expect(store.dispatch('curations/linkNewStatus', { curation, data: {} })).resolves.toBe(result)
        expect(curation.curation_statuses).toEqual([status])
        expect(store.state.curations.items).toEqual([])
    })

    it('updates and unlinks status entries with their current return contracts', async () => {
        const original = { id: 3, pivot: { id: 153, status_date: '2024-01-01' } }
        const updated = { id: 3, pivot: { id: 153, status_date: '2025-01-01' } }
        const curation = { id: 16, curation_statuses: [original] }
        const updateResult = response(updated)
        const deleteResult = response({}, { status: 204 })
        window.axios.put.mockResolvedValue(updateResult)
        window.axios.delete.mockResolvedValue(deleteResult)

        await expect(store.dispatch('curations/updateStatusDate', {
            curation,
            pivotId: 153,
            statusDate: '2025-01-01',
        })).resolves.toBe(updateResult)
        expect(window.axios.put).toHaveBeenCalledWith(
            '/api/curations/16/statuses/153',
            { status_date: '2025-01-01' }
        )
        expect(curation.curation_statuses).toEqual([updated])

        await expect(store.dispatch('curations/unlinkStatus', { curation, pivotId: 153 })).resolves.toBeUndefined()
        expect(window.axios.delete).toHaveBeenCalledWith('/api/curations/16/statuses/153')
        expect(curation.curation_statuses).toEqual([])
        expect(store.state.curations.items[0]).toEqual(curation)
    })

    it('rejects status failures with error.response without mutating the curation', async () => {
        const curation = { id: 17, curation_statuses: [] }
        const error = { response: { status: 422 } }
        window.axios.post.mockRejectedValue(error)

        await expect(store.dispatch('curations/linkNewStatus', { curation, data: {} })).rejects.toBe(error.response)
        expect(curation.curation_statuses).toEqual([])
        expect(store.state.curations.items).toEqual([])
    })

    it('links, updates, and unlinks classifications while mutating the supplied curation', async () => {
        const curation = { id: 18, classifications: [] }
        const payload = { classification_id: 4 }
        const linked = { id: 4, pivot: { id: 181, notes: 'first' } }
        const updated = { id: 4, pivot: { id: 181, notes: 'updated' } }
        const linkResult = response(linked)
        const updateResult = response(updated)
        window.axios.post.mockResolvedValue(linkResult)
        window.axios.put.mockResolvedValue(updateResult)
        window.axios.delete.mockResolvedValue(response({}, { status: 204 }))

        await expect(store.dispatch('curations/linkNewClassification', { curation, data: payload })).resolves.toBe(linkResult)
        expect(window.axios.post).toHaveBeenCalledWith('/api/curations/18/classifications', payload)
        expect(curation.classifications).toEqual([linked])

        const updatePayload = { notes: 'updated' }
        await expect(store.dispatch('curations/updateClassification', {
            curation,
            pivotId: 181,
            data: updatePayload,
        })).resolves.toBe(updateResult)
        expect(window.axios.put).toHaveBeenCalledWith(
            '/api/curations/18/classifications/181',
            updatePayload
        )
        expect(curation.classifications).toEqual([updated])

        await expect(store.dispatch('curations/unlinkClassification', {
            curation,
            pivotId: 181,
        })).resolves.toBeUndefined()
        expect(window.axios.delete).toHaveBeenCalledWith('/api/curations/18/classifications/181')
        expect(curation.classifications).toEqual([])
        expect(store.state.curations.items[0]).toEqual(curation)
    })

    it('preserves the classification actions\' differing rejection contracts', async () => {
        const curation = { id: 19, classifications: [] }
        const rawError = new Error('link failed')
        const wrappedError = { response: { status: 422 } }
        window.axios.post.mockRejectedValue(rawError)
        window.axios.put.mockRejectedValueOnce(wrappedError)
        window.axios.delete.mockRejectedValueOnce(wrappedError)

        await expect(store.dispatch('curations/linkNewClassification', { curation, data: {} })).rejects.toBe(rawError)
        await expect(store.dispatch('curations/updateClassification', {
            curation,
            pivotId: 1,
            data: {},
        })).rejects.toBe(wrappedError.response)
        await expect(store.dispatch('curations/unlinkClassification', {
            curation,
            pivotId: 1,
        })).rejects.toBe(wrappedError.response)
        expect(curation.classifications).toEqual([])
    })

    it('updates ownership, dispatches a refresh, and returns without awaiting that refresh', async () => {
        const curation = { id: 20 }
        const payload = {
            curation,
            expertPanelId: 8,
            startDate: '2025-06-01',
            notes: 'Transferred for E2E',
        }
        const ownerResult = response({ curation_id: 20, expert_panels: [{ id: 8 }] })
        const fetched = response({ data: { id: 20, expert_panel_id: 8 } })
        window.axios.post.mockResolvedValue(ownerResult)
        window.axios.get.mockResolvedValue(fetched)

        await expect(store.dispatch('curations/updateOwner', payload)).resolves.toBe(ownerResult)
        expect(window.axios.post).toHaveBeenCalledWith('/api/curations/20/owner', {
            expert_panel_id: 8,
            start_date: '2025-06-01',
            notes: 'Transferred for E2E',
        })
        expect(window.axios.get).toHaveBeenCalledWith('/api/curations/20')
        await Promise.resolve()
        expect(store.state.curations.items).toEqual([fetched.data.data])
    })

    it('passes through ownership update failures and does not refresh', async () => {
        const error = new Error('owner failed')
        window.axios.post.mockRejectedValue(error)

        await expect(store.dispatch('curations/updateOwner', {
            curation: { id: 21 },
            expertPanelId: 8,
            startDate: null,
            notes: null,
        })).rejects.toBe(error)
        expect(window.axios.get).not.toHaveBeenCalled()
    })
})
