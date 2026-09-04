import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminUsers from '../../../resources/assets/js/components/admin/Users.vue'

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
            : [managedUser] })),
        put: vi.fn(),
        patch: vi.fn(),
    }
})

describe('User administration', () => {
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

    it('updates identity, roles, and direct permissions without a membership payload', async () => {
        window.axios.put.mockResolvedValue({ data: { ...managedUser, name: 'Updated Managed User' } })
        const wrapper = mountPage(userWith('list users', 'update users'))
        await flushPromises()

        await buttonByText(wrapper, 'Edit').trigger('click')
        await wrapper.get('#user-name').setValue('Updated Managed User')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/users/7', {
            name: 'Updated Managed User',
            email: 'managed@example.com',
            role_ids: [2],
            permission_ids: [9],
        })
        expect(window.axios.put.mock.calls[0][1]).not.toHaveProperty('expert_panels')
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
        await wrapper.get('form').trigger('submit')
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
