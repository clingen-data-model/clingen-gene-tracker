import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminWorkingGroups from '../../../resources/assets/js/components/admin/WorkingGroups.vue'

function userWith(...permissions) {
    return { hasPermission: permission => permissions.includes(permission) }
}

function mountPage(user = userWith()) {
    return mount(AdminWorkingGroups, {
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

beforeEach(() => {
    window.axios = {
        get: vi.fn().mockResolvedValue({ data: [] }),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
    window.confirm = vi.fn(() => true)
})

describe('Working Group administration', () => {
    it('renders panel counts and only permitted controls', async () => {
        window.axios.get.mockResolvedValue({
            data: [{ id: 1, name: 'Known Working Group', expert_panels_count: 3 }],
        })
        const wrapper = mountPage(userWith('list working-groups', 'update working-groups'))
        await flushPromises()

        expect(wrapper.text()).toContain('Known Working Group')
        expect(wrapper.text()).toContain('3')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).not.toContain('Add Working Group')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('creates and updates a working group through the explicit contracts', async () => {
        window.axios.post.mockResolvedValue({ data: { id: 8, name: 'Created Group' } })
        window.axios.put.mockResolvedValue({ data: { id: 8, name: 'Updated Group', expert_panels_count: 0 } })
        const wrapper = mountPage(userWith(
            'list working-groups',
            'create working-groups',
            'update working-groups',
        ))
        await flushPromises()

        await buttonByText(wrapper, 'Add Working Group').trigger('click')
        await wrapper.get('#working-group-name').setValue('Created Group')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(window.axios.post).toHaveBeenCalledWith('/api/admin/working-groups', { name: 'Created Group' })

        await buttonByText(wrapper, 'Edit').trigger('click')
        await wrapper.get('#working-group-name').setValue('Updated Group')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/working-groups/8', { name: 'Updated Group' })
        expect(wrapper.text()).toContain('Working group updated successfully.')
    })

    it('shows backend validation and delete conflict messages', async () => {
        window.axios.get.mockResolvedValue({
            data: [{ id: 9, name: 'Referenced Group', expert_panels_count: 1 }],
        })
        window.axios.post.mockRejectedValue({
            response: { status: 422, data: { errors: { name: ['The name field is required.'] } } },
        })
        window.axios.delete.mockRejectedValue({
            response: { status: 409, data: { message: 'This working group has expert panels and cannot be deleted.' } },
        })
        const wrapper = mountPage(userWith(
            'list working-groups',
            'create working-groups',
            'delete working-groups',
        ))
        await flushPromises()

        await buttonByText(wrapper, 'Add Working Group').trigger('click')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The name field is required.')

        await buttonByText(wrapper, 'Cancel').trigger('click')
        await buttonByText(wrapper, 'Delete').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('This working group has expert panels and cannot be deleted.')
    })
})
