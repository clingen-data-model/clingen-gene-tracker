import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminUsers from '../../../resources/assets/js/components/admin/Users.vue'
import SearchSelect from '../../../resources/assets/js/components/forms/SearchSelect.vue'

function userWith(...permissions) {
    return { hasPermission: permission => permissions.includes(permission) }
}

function mountPage(user = userWith()) {
    return mount(AdminUsers, {
        global: {
            plugins: [
                createBootstrap(),
                { install: registerBootstrapVueNext },
                createStore({ getters: { getUser: () => user } }),
            ],
        },
    })
}

function buttonByText(wrapper, text) {
    return wrapper.findAll('button').find(button => button.text().includes(text))
}

const managedUser = {
    id: 7,
    name: 'Managed User',
    email: 'managed@example.com',
    roles: [{ id: 2, name: 'viewer' }],
    permissions: [{ id: 9, name: 'list curations' }],
    deactivated_at: null,
    expert_panels_count: 3,
    affiliations_count: 1,
}

beforeEach(() => {
    window.confirm = vi.fn(() => true)
    window.axios = {
        get: vi.fn(url => Promise.resolve({ data: url.endsWith('/options')
            ? { roles: [{ id: 2, name: 'viewer' }], permissions: [{ id: 9, name: 'list curations' }] }
            : { data: [managedUser], total: 30 } })),
        put: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
    }
})

describe('User administration', () => {
    it('creates a user with roles, permissions and membership flags without a password field', async () => {
        const panel = { id: 42, name: 'Selectable Expert Panel' }
        window.axios.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('/options')
            ? { roles: managedUser.roles, permissions: managedUser.permissions, expert_panels: [panel] }
            : { data: [], total: 0 } }))
        window.axios.post.mockResolvedValue({ data: managedUser })
        const wrapper = mountPage(userWith('list users', 'create users'))
        await flushPromises()
        await buttonByText(wrapper, 'Add User').trigger('click')
        expect(wrapper.text()).toContain('Create User')
        expect(wrapper.find('input[type="password"]').exists()).toBe(false)
        await wrapper.get('#user-name').setValue('Managed User')
        await wrapper.get('#user-email').setValue('managed@example.com')
        await wrapper.get('#user-roles').setValue(['2'])
        await wrapper.get('#user-permissions').setValue(['9'])
        await buttonByText(wrapper, 'Add Expert Panel').trigger('click')
        wrapper.getComponent(SearchSelect).vm.$emit('update:modelValue', panel)
        await flushPromises()
        const flags = wrapper.findAll('input[type="checkbox"]')
        await flags[0].setValue(true)
        await flags[2].setValue(true)
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(window.axios.post).toHaveBeenCalledWith('/api/admin/users', {
            name: 'Managed User', email: 'managed@example.com', role_ids: [2], permission_ids: [9],
            expert_panels: [{ id: 42, is_curator: true, is_coordinator: false, can_edit_curations: true }],
        })
        expect(window.axios.put).not.toHaveBeenCalled()
        expect(wrapper.text()).toContain('User created successfully.')
        wrapper.unmount()
    })

    it('keeps the create form and input when validation fails', async () => {
        window.axios.post.mockRejectedValue({ response: { status: 422, data: { errors: { email: ['The email has already been taken.'] } } } })
        const wrapper = mountPage(userWith('list users', 'create users'))
        await flushPromises()
        await buttonByText(wrapper, 'Add User').trigger('click')
        await wrapper.get('#user-email').setValue('duplicate@example.com')
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The email has already been taken.')
        expect(wrapper.get('#user-email').element.value).toBe('duplicate@example.com')
        expect(buttonByText(wrapper, 'Create User').exists()).toBe(true)
        wrapper.unmount()
    })

    it('requests server pages when pagination changes', async () => {
        const wrapper = mountPage(userWith('list users'))
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/users', { params: { page: 1, per_page: 25 } })
        wrapper.findComponent({ name: 'BPagination' }).vm.$emit('update:modelValue', 2)
        await flushPromises()
        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/users', { params: { page: 2, per_page: 25 } })
    })

    it('renders account summaries and only permitted controls', async () => {
        const wrapper = mountPage(userWith('list users', 'update users'))
        await flushPromises()

        expect(wrapper.text()).toContain('Managed User')
        expect(wrapper.text()).toContain('managed@example.com')
        expect(wrapper.text()).toContain('viewer')
        expect(wrapper.text()).toContain('Active')
        expect(wrapper.text()).toContain('3')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).not.toContain('Deactivate')
        expect(wrapper.text()).not.toContain('Add User')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('updates identity, roles, and direct permissions with an explicit membership payload', async () => {
        window.axios.put.mockResolvedValue({ data: { ...managedUser, name: 'Updated Managed User' } })
        const wrapper = mountPage(userWith('list users', 'update users'))
        await flushPromises()

        await buttonByText(wrapper, 'Edit').trigger('click')
        await wrapper.get('#user-name').setValue('Updated Managed User')
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()

        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/users/7', {
            name: 'Updated Managed User',
            email: 'managed@example.com',
            role_ids: [2],
            permission_ids: [9],
            expert_panels: [],
        })
        expect(window.axios.put.mock.calls[0][1].expert_panels).toEqual([])
        expect(window.axios.put.mock.calls[0][1]).not.toHaveProperty('affiliations')
        expect(wrapper.text()).toContain('User updated successfully.')
    })

    it('shows validation feedback from the backend', async () => {
        window.axios.put.mockRejectedValue({
            response: { status: 422, data: { errors: { email: ['The email has already been taken.'] } } },
        })
        const wrapper = mountPage(userWith('list users', 'update users'))
        await flushPromises()

        await buttonByText(wrapper, 'Edit').trigger('click')
        await wrapper.get('form:not([role="search"])').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The email has already been taken.')
    })

    it('confirms and reflects deactivate/reactivate lifecycle changes', async () => {
        window.axios.patch
            .mockResolvedValueOnce({ data: { ...managedUser, deactivated_at: '2026-09-03T12:00:00Z' } })
            .mockResolvedValueOnce({ data: managedUser })
        const wrapper = mountPage(userWith('list users', 'deactivate users'))
        await flushPromises()

        await buttonByText(wrapper, 'Deactivate').trigger('click')
        await flushPromises()
        expect(window.confirm).toHaveBeenCalledWith('Deactivate Managed User?')
        expect(window.axios.patch).toHaveBeenCalledWith('/api/admin/users/7/deactivate')
        expect(wrapper.text()).toContain('Deactivated')

        await buttonByText(wrapper, 'Reactivate').trigger('click')
        await flushPromises()
        expect(window.axios.patch).toHaveBeenCalledWith('/api/admin/users/7/reactivate')
        expect(wrapper.text()).toContain('Active')
    })
})
