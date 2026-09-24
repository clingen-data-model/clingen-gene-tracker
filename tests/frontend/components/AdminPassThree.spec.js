import { defineComponent, ref } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Users from '../../../resources/assets/js/components/admin/Users.vue'
import ExpertPanels from '../../../resources/assets/js/components/admin/ExpertPanels.vue'
import Affiliations from '../../../resources/assets/js/components/admin/Affiliations.vue'
import WorkingGroups from '../../../resources/assets/js/components/admin/WorkingGroups.vue'
import Emails from '../../../resources/assets/js/components/admin/Emails.vue'
import Notifications from '../../../resources/assets/js/components/admin/Notifications.vue'
import Administration from '../../../resources/assets/js/components/admin/Administration.vue'
import Memberships from '../../../resources/assets/js/components/admin/ExpertPanelMemberships.vue'
import SearchSelect from '../../../resources/assets/js/components/forms/SearchSelect.vue'

const stubs = {
    BCard: { template: '<div><slot /></div>' },
    BForm: { template: '<form><slot /></form>' },
    BFormGroup: { template: '<div><slot /></div>' },
    BFormInput: true, BFormSelect: true, BFormInvalidFeedback: true,
    BButton: { template: '<button><slot /></button>' },
    BSpinner: true, BAlert: true, BBadge: true,
    BPagination: { name: 'BPagination', props: ['modelValue'], emits: ['update:modelValue'], template: '<div />' },
    BTable: { props: ['items'], template: '<div><div v-for="item in items" :key="item.id"><slot name="cell(actions)" :item="item" /></div></div>' },
    RouterLink: { props: ['to'], template: '<a><slot /></a>' }, RouterView: true,
}
const wrappers = []
function mountPage(component, permissions = true) {
    const wrapper = mount(component, { global: { stubs, plugins: [createStore({ getters: { getUser: () => ({
        hasPermission: () => permissions, hasRole: () => permissions,
    }) } })] } })
    wrappers.push(wrapper)
    return wrapper
}
function button(wrapper, text) { return wrapper.findAll('button').find(item => item.text() === text) }

beforeEach(() => {
    window.axios = { get: vi.fn(url => Promise.resolve({ data: url.endsWith('/options')
        ? { roles: [], permissions: [], affiliations: [], expert_panels: [] }
        : url === '/api/working-groups' || url === '/api/admin/working-groups' ? [] : { data: [], total: 60 } })), put: vi.fn(), post: vi.fn() }
})
afterEach(() => { wrappers.splice(0).forEach(wrapper => wrapper.unmount()); vi.useRealTimers() })

describe('Admin search', () => {
    it.each([[Users, 'users'], [ExpertPanels, 'expert-panels'], [Affiliations, 'affiliations'], [Emails, 'emails'], [Notifications, 'notifications']])(
        '%s searches server data from page one and keeps the term during pagination', async (component, path) => {
            const wrapper = mountPage(component)
            await flushPromises()
            wrapper.getComponent({ name: 'BPagination' }).vm.$emit('update:modelValue', 2)
            await flushPromises()
            await wrapper.get('input[type="search"]').setValue(' Needle ')
            await wrapper.get('form[role="search"]').trigger('submit')
            await flushPromises()
            expect(window.axios.get).toHaveBeenCalledWith(`/api/admin/${path}`, { params: { page: 1, per_page: 25, search: 'Needle' } })
            wrapper.getComponent({ name: 'BPagination' }).vm.$emit('update:modelValue', 2)
            await flushPromises()
            expect(window.axios.get).toHaveBeenCalledWith(`/api/admin/${path}`, { params: { page: 2, per_page: 25, search: 'Needle' } })
            await button(wrapper, 'Clear').trigger('click')
            await flushPromises()
            expect(window.axios.get.mock.calls.filter(([url]) => url === `/api/admin/${path}`).at(-1))
                .toEqual([`/api/admin/${path}`, { params: { page: 1, per_page: 25 } }])
        },
    )
    it('searches Working Groups by name', async () => {
        const wrapper = mountPage(WorkingGroups)
        await flushPromises()
        await wrapper.get('input[type="search"]').setValue('Cardio')
        await wrapper.get('form[role="search"]').trigger('submit')
        await flushPromises()
        expect(window.axios.get).toHaveBeenLastCalledWith('/api/admin/working-groups', { params: { search: 'Cardio' } })
    })
})

describe('Admin navigation', () => {
    it('follows the historical order with Curation Statuses next to Curation Types', () => {
        const wrapper = mountPage(Administration)
        expect(wrapper.findAll('nav a').map(link => link.text())).toEqual([
            'Dashboard', 'Users', 'Expert Panels', 'Affiliations', 'Working Groups', 'Curation Types', 'Curation Statuses',
            'Rationales', 'Modes of Inheritance', 'Upload Categories', 'Outdated Phenotype Labels', 'API Clients', 'Emails', 'Notifications',
        ])
    })
    it('preserves the existing permission-sensitive links', () => {
        const wrapper = mountPage(Administration, false)
        expect(wrapper.findAll('nav a').map(link => link.text())).toEqual([
            'Dashboard', 'Affiliations', 'Upload Categories', 'Outdated Phenotype Labels', 'API Clients', 'Emails', 'Notifications',
        ])
    })
})

describe('User membership editing', () => {
    it('loads existing selections and flags and saves their explicit membership contract', async () => {
        const panel = { id: 42, name: 'Existing Panel', pivot: { is_curator: 1, is_coordinator: 0, can_edit_curations: 1 } }
        const user = { id: 4, name: 'Managed User', email: 'managed@example.com', roles: [], permissions: [], expert_panels: [panel] }
        window.axios.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('/options') ? { roles: [], permissions: [], expert_panels: [panel] } : { data: [user], total: 1 } }))
        window.axios.put.mockResolvedValue({ data: user })
        const wrapper = mountPage(Users)
        await flushPromises()
        await button(wrapper, 'Edit').trigger('click')
        expect(wrapper.text()).toContain('Existing Panel')
        const checks = wrapper.findAll('input[type="checkbox"]')
        expect(checks.map(input => input.element.checked)).toEqual([true, false, true])
        await checks[1].setValue(true)
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/users/4', expect.objectContaining({ expert_panels: [{ id: 42, is_curator: true, is_coordinator: true, can_edit_curations: true }] }))
    })

    it('supports keyboard panel selection, prevents duplicate options, and removes rows', async () => {
        vi.useFakeTimers()
        const panelOptions = [{ id: 1, name: 'Alpha Expert Panel' }, { id: 2, name: 'Beta Expert Panel' }]
        const Harness = defineComponent({ components: { Memberships }, setup: () => ({ rows: ref([]), panelOptions }), template: '<form><Memberships v-model="rows" :panels="panelOptions" /></form>' })
        const wrapper = mount(Harness, { attachTo: document.body })
        wrappers.push(wrapper)
        await button(wrapper, 'Add Expert Panel').trigger('click')
        const input = wrapper.get('input[role="combobox"]')
        await input.setValue('Alpha')
        await vi.advanceTimersByTimeAsync(250)
        expect(wrapper.get('[role="option"]').text()).toBe('Alpha Expert Panel')
        const event = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true, bubbles: true })
        input.element.dispatchEvent(event)
        expect(event.defaultPrevented).toBe(true)
        await input.trigger('keyup', { key: 'Enter' })
        expect(wrapper.text()).toContain('Alpha Expert Panel')
        await button(wrapper, 'Add Expert Panel').trigger('click')
        expect(wrapper.findAllComponents(SearchSelect)[1].props('options')).toEqual([panelOptions[1]])
        await wrapper.get('[aria-label="Remove Expert Panel 1"]').trigger('click')
        expect(wrapper.findAllComponents(SearchSelect)).toHaveLength(1)
        expect(wrapper.getComponent(SearchSelect).props('options')).toEqual(panelOptions)
    })
})

describe('Expert Panel affiliation selection', () => {
    it('includes an existing affiliation when creating and changing a panel', async () => {
        const affiliation = { id: 8, name: 'Chosen Affiliation', clingen_id: 90123 }
        window.axios.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('/options') ? { affiliations: [affiliation] } : url === '/api/working-groups' ? [] : { data: [], total: 0 } }))
        window.axios.post.mockResolvedValue({ data: { id: 5, name: 'New Expert Panel', affiliation_id: 8, affiliation } })
        window.axios.put.mockResolvedValue({ data: { id: 5, name: 'New Expert Panel', affiliation_id: null, affiliation: null } })
        const wrapper = mountPage(ExpertPanels)
        await flushPromises()
        await button(wrapper, 'Add Expert Panel').trigger('click')
        wrapper.getComponent({ name: 'BFormInput' }).vm.$emit('update:modelValue', 'New Expert Panel')
        wrapper.getComponent(SearchSelect).vm.$emit('update:modelValue', affiliation)
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(window.axios.post).toHaveBeenCalledWith('/api/admin/expert-panels', { name: 'New Expert Panel', working_group_id: null, affiliation_id: 8 })
        await button(wrapper, 'Edit').trigger('click')
        expect(wrapper.getComponent(SearchSelect).props('modelValue')).toEqual(affiliation)
        wrapper.getComponent(SearchSelect).vm.$emit('update:modelValue', null)
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/expert-panels/5', expect.objectContaining({ affiliation_id: null }))
    })
})
