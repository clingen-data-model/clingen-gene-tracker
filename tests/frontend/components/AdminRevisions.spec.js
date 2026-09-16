import { flushPromises, mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Users from '../../../resources/assets/js/components/admin/Users.vue'
import ExpertPanels from '../../../resources/assets/js/components/admin/ExpertPanels.vue'
import Affiliations from '../../../resources/assets/js/components/admin/Affiliations.vue'
import RevisionHistory from '../../../resources/assets/js/components/admin/RevisionHistory.vue'

const stubs = {
    BButton: { template: '<button><slot /></button>' },
    BAlert: true,
    BBadge: { template: '<span><slot /></span>' },
    BPagination: true,
    BTable: {
        props: ['items', 'fields'],
        template: `<table><tbody><tr v-for="item in items" :key="item.id"><td v-for="field in fields" :key="field.key">
            <slot :name="'cell(' + field.key + ')'" :item="item">{{ item[field.key] }}</slot>
        </td></tr></tbody></table>`,
    },
}
const revision = {
    id: 12, field: 'Name', old_value: 'Original name', new_value: '<b>Changed name</b>',
    changed_by: 'Revision Administrator', changed_at: '2026-01-15T12:00:00Z',
}

beforeEach(() => {
    window.axios = { get: vi.fn(url => Promise.resolve({ data: url.endsWith('/revisions')
        ? { data: [revision] }
        : url.endsWith('/options') ? { roles: [], permissions: [] }
            : url === '/api/working-groups' ? [] : { data: [{ id: 7, name: 'Example record' }], total: 1 } })) }
})

describe('Admin revision actions', () => {
    it.each([
        [Users, 'users', 'list users'],
        [ExpertPanels, 'expert-panels', 'list expert-panels'],
        [Affiliations, 'affiliations', null],
    ])('opens the correct history endpoint from %s', async (component, path, permission) => {
        const wrapper = mount(component, {
            global: { stubs, plugins: [createStore({ getters: { getUser: () => ({
                hasPermission: value => value === permission,
                hasRole: value => value === 'admin',
            }) } })] },
        })
        await flushPromises()
        const button = wrapper.findAll('button').find(item => item.text() === 'Revisions')
        expect(button).toBeDefined()
        expect(window.axios.get).not.toHaveBeenCalledWith(`/api/admin/${path}/7/revisions`)
        await button.trigger('click')
        await flushPromises()
        expect(window.axios.get).toHaveBeenCalledWith(`/api/admin/${path}/7/revisions`)
        expect(wrapper.get('section').text()).toContain('Original name')
        expect(wrapper.get('section').text()).toContain('<b>Changed name</b>')
        await wrapper.findAll('button').find(item => item.text() === 'Close Revisions').trigger('click')
        expect(wrapper.find('section').exists()).toBe(false)
    })

    it.each([Users, ExpertPanels, Affiliations])('hides history when entity access is unavailable in %s', async component => {
        const wrapper = mount(component, { global: { stubs, plugins: [createStore({ getters: {
            getUser: () => ({ hasPermission: () => false, hasRole: () => false }),
        } })] } })
        await flushPromises()
        expect(wrapper.findAll('button').some(button => button.text() === 'Revisions')).toBe(false)
    })
})

function history() {
    return mount(RevisionHistory, { props: { endpoint: '/api/admin/users/7/revisions', name: 'Example record' }, global: { stubs } })
}

describe('Read-only revision history', () => {
    it('renders all columns, escapes HTML, and distinguishes null and empty values', async () => {
        window.axios.get.mockResolvedValue({ data: { data: [revision, {
            ...revision, id: 13, old_value: null, new_value: '',
        }] } })
        const wrapper = history()
        expect(wrapper.get('[role="status"]').text()).toContain('Loading')
        await flushPromises()
        expect(wrapper.findAll('th').map(cell => cell.text())).toEqual([
            'Changed field', 'Old value', 'New value', 'Changed by', 'Changed at',
        ])
        expect(wrapper.findAll('tbody tr')[0].findAll('td').map(cell => cell.text())).toEqual([
            'Name', 'Original name', '<b>Changed name</b>', 'Revision Administrator',
            new Date(revision.changed_at).toLocaleString(),
        ])
        expect(wrapper.find('tbody b').exists()).toBe(false)
        expect(wrapper.text()).toContain('(null)')
        expect(wrapper.text()).toContain('(empty)')
    })

    it('shows an empty history as a normal state', async () => {
        window.axios.get.mockResolvedValue({ data: { data: [] } })
        const wrapper = history()
        await flushPromises()
        expect(wrapper.text()).toContain('No revision history found.')
        expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    })

    it('shows load errors without claiming history is empty', async () => {
        window.axios.get.mockRejectedValue(new Error('Unavailable'))
        const wrapper = history()
        await flushPromises()
        expect(wrapper.get('[role="alert"]').text()).toBe('Unable to load revision history.')
        expect(wrapper.text()).not.toContain('No revision history found.')
    })

    it('ignores a stale response when another record is selected', async () => {
        let resolveFirst
        window.axios.get.mockImplementationOnce(() => new Promise(resolve => { resolveFirst = resolve }))
            .mockResolvedValueOnce({ data: { data: [] } })
        const wrapper = history()
        await wrapper.setProps({ endpoint: '/api/admin/users/8/revisions' })
        await flushPromises()
        resolveFirst({ data: { data: [revision] } })
        await flushPromises()
        expect(wrapper.text()).toContain('No revision history found.')
        expect(wrapper.text()).not.toContain('Original name')
    })
})
