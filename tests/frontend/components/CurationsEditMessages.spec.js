import { nextTick } from 'vue'
import { shallowMount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, expect, it, vi } from 'vitest'
import Edit from '../../../resources/assets/js/components/Curations/Edit.vue'
import messages from '../../../resources/assets/js/store/modules/messages'

const routerPush = vi.fn()

vi.mock('vue-router', () => ({
    useRoute: () => ({
        path: '/curations/42/edit/',
        hash: '',
        fullPath: '/curations/42/edit/',
    }),
    useRouter: () => ({ push: routerPush }),
}))

function makeStore() {
    const curation = {
        id: 42,
        gene_symbol: 'E2E-ARCHIVED',
        is_archived: true,
        expert_panel: { name: 'E2E Expert Panel' },
        rationales: [],
    }
    const user = {
        canEditCuration: () => false,
        canManageArchive: () => false,
    }

    return createStore({
        state: {
            features: { transferEnabled: false },
        },
        getters: {
            getUser: () => user,
        },
        modules: {
            curations: {
                namespaced: true,
                state: () => ({ items: [curation] }),
                getters: {
                    Items: state => state.items,
                    getItemById: state => id => state.items.find(item => item.id == id),
                },
                actions: {
                    fetchItem: () => Promise.resolve(curation),
                    storeItemUpdates: () => Promise.reject(new Error('Archived curation must not be saved')),
                },
            },
            messages: {
                ...messages,
                state: () => ({ info: [], errors: [] }),
            },
        },
    })
}

describe('Curations/Edit archived save guard', () => {
    it('adds the archived warning as an error without committing an unknown alert mutation', async () => {
        const store = makeStore()
        const commit = vi.spyOn(store, 'commit')
        const wrapper = shallowMount(Edit, {
            props: { id: 42 },
            global: { plugins: [store] },
        })

        await nextTick()
        wrapper.vm.updateCuration()

        const message = 'This curation is archived and cannot be edited.'
        expect(commit).toHaveBeenCalledWith('messages/addError', message)
        expect(commit).not.toHaveBeenCalledWith('messages/addAlert', expect.anything())
        expect(store.getters['messages/errors']).toEqual([message])
        expect(store.getters['messages/info']).toEqual([])
    })
})
