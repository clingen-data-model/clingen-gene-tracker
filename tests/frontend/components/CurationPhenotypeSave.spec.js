import { defineComponent } from 'vue'
import { shallowMount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, expect, it, vi } from 'vitest'
import Edit from '../../../resources/assets/js/components/Curations/Edit.vue'

vi.mock('vue-router', () => ({
    useRoute: () => ({ path: '/curations/42/edit', hash: '#phenotypes', fullPath: '/curations/42/edit#phenotypes' }),
    useRouter: () => ({ push: vi.fn() }),
}))

describe('phenotype options after saving', () => {
    it.each([true, false])('refreshes options only after a successful save (success: %s)', async succeeds => {
        const refresh = vi.fn().mockResolvedValue()
        const save = vi.fn(() => succeeds ? Promise.resolve({ data: {} }) : Promise.reject({ response: { data: { errors: { phenotypes: ['Invalid selection'] } } } }))
        const curation = { id: 42, gene_symbol: 'LMNA', phenotypes: [{ id: 1, label_obsolete: true }, { id: 2, label_obsolete: false }], rationales: [] }
        const store = createStore({
            state: { features: {} },
            getters: { getUser: () => ({ canEditCuration: () => true }) },
            mutations: { 'messages/addInfo': vi.fn() },
            modules: { curations: {
                namespaced: true,
                getters: { Items: () => [curation], getItemById: () => () => curation },
                actions: { fetchItem: () => Promise.resolve(), storeItemUpdates: save },
            } },
        })
        const wrapper = shallowMount(Edit, { props: { id: 42 }, global: {
            plugins: [store],
            renderStubDefaultSlot: true,
            stubs: { Phenotypes: defineComponent({
                setup(_, { expose }) { expose({ refreshPhenotypes: refresh }); return () => null },
            }) },
        } })
        await flushPromises()
        expect(refresh).not.toHaveBeenCalled()
        await wrapper.vm.updateCuration()
        expect(save).toHaveBeenCalledOnce()
        expect(save.mock.calls[0][1].phenotypes).toEqual([{ id: 2, label_obsolete: false }])
        expect(curation.phenotypes).toHaveLength(2)
        expect(refresh).toHaveBeenCalledTimes(succeeds ? 1 : 0)
        wrapper.unmount()
    })
})
