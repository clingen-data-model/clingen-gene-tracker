import { describe, expect, it } from 'vitest'
import { createStore } from 'vuex'
import users from '../../../resources/assets/js/store/modules/users'
import workingGroups from '../../../resources/assets/js/store/modules/working_groups'

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

describe('working-group mutation index safety', () => {
    it('removes the requested existing item', () => {
        const store = makeStore('workingGroups', workingGroups)
        store.state.workingGroups.items.push({ id: 1 }, { id: 2 })

        store.commit('workingGroups/removeItem', 1)

        expect(store.state.workingGroups.items).toEqual([{ id: 2 }])
    })

    it('leaves all items intact when the requested item is missing', () => {
        const store = makeStore('workingGroups', workingGroups)
        store.state.workingGroups.items.push({ id: 1 }, { id: 2 })

        store.commit('workingGroups/removeItem', 999)

        expect(store.state.workingGroups.items).toEqual([{ id: 1 }, { id: 2 }])
    })
})

describe('user mutation index safety', () => {
    it('replaces an existing user', () => {
        const store = makeStore('users', users)
        store.state.users.items.push({ id: 1, name: 'Before' }, { id: 2, name: 'Other' })
        const updated = { id: 1, name: 'After' }

        store.commit('users/updateItem', updated)

        expect(store.state.users.items).toEqual([updated, { id: 2, name: 'Other' }])
    })

    it('leaves the array unchanged without creating a -1 property when the user is missing', () => {
        const store = makeStore('users', users)
        const existing = { id: 1, name: 'Existing' }
        store.state.users.items.push(existing)

        store.commit('users/updateItem', { id: 999, name: 'Missing' })

        expect(store.state.users.items).toEqual([existing])
        expect(Object.hasOwn(store.state.users.items, '-1')).toBe(false)
    })
})
