import { beforeEach, describe, expect, it } from 'vitest'
import { createStore } from 'vuex'
import messages from '../../../resources/assets/js/store/modules/messages'

function makeStore() {
    return createStore({
        modules: {
            messages: {
                ...messages,
                state: () => ({ info: [], errors: [] }),
            },
        },
    })
}

describe('messages Vuex store', () => {
    let store

    beforeEach(() => {
        store = makeStore()
    })

    it('adds unique info messages and removes them by index', () => {
        store.commit('messages/addInfo', 'Saved')
        store.commit('messages/addInfo', 'Saved')
        store.commit('messages/addInfo', 'Created')

        expect(store.getters['messages/info']).toEqual(['Saved', 'Created'])

        store.commit('messages/removeInfo', 0)
        expect(store.getters['messages/info']).toEqual(['Created'])
    })

    it('adds error messages, including duplicates, and removes them by index', () => {
        store.commit('messages/addError', 'Failed')
        store.commit('messages/addError', 'Failed')

        expect(store.getters['messages/errors']).toEqual(['Failed', 'Failed'])

        store.commit('messages/removeError', 1)
        expect(store.getters['messages/errors']).toEqual(['Failed'])
    })

    it('returns the combined live alert state', () => {
        store.commit('messages/addInfo', 'Saved')
        store.commit('messages/addError', 'Failed')

        expect(store.getters['messages/all']).toBe(store.state.messages)
        expect(store.getters['messages/all']).toEqual({
            info: ['Saved'],
            errors: ['Failed'],
        })
    })
})
