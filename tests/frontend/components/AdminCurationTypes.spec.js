import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CurationTypes from '../../../resources/assets/js/components/admin/CurationTypes.vue'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'

function userWith(...permissions) {
    return {
        hasPermission: permission => permissions.includes(permission),
    }
}

function mountPage(user) {
    return mount(CurationTypes, {
        global: {
            plugins: [
                createBootstrap(),
                { install: registerBootstrapVueNext },
                createStore({ getters: { getUser: () => user } }),
            ],
        },
    })
}

beforeEach(() => {
    window.axios = {
        get: vi.fn().mockResolvedValue({
            data: [{ id: 1, name: 'Gene Disease Validity', description: 'GDV curation' }],
        }),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
    window.confirm = vi.fn(() => true)
})

describe('Admin Curation Types', () => {
    it('renders the curation type list and operation controls for a fully authorized user', async () => {
        const wrapper = mountPage(userWith(
            'list curation-types',
            'create curation-types',
            'update curation-types',
            'delete curation-types',
        ))
        await flushPromises()

        expect(wrapper.get('h2').text()).toBe('Curation Types')
        expect(wrapper.text()).toContain('Gene Disease Validity')
        expect(wrapper.get('button').text()).toContain('Add Curation Type')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).toContain('Delete')
    })

    it('hides create, edit, and delete controls without their permissions', async () => {
        const wrapper = mountPage(userWith('list curation-types'))
        await flushPromises()

        expect(wrapper.text()).toContain('Gene Disease Validity')
        expect(wrapper.text()).not.toContain('Add Curation Type')
        expect(wrapper.text()).not.toContain('Edit')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('shows backend validation and then supports a successful create', async () => {
        window.axios.post
            .mockRejectedValueOnce({
                response: { status: 422, data: { errors: { name: ['The name has already been taken.'] } } },
            })
            .mockResolvedValueOnce({
                data: { id: 2, name: 'New Curation Type', description: 'A new type' },
            })
        const wrapper = mountPage(userWith('list curation-types', 'create curation-types'))
        await flushPromises()

        await wrapper.get('button').trigger('click')
        await wrapper.get('#curation-type-name').setValue('New Curation Type')
        await wrapper.get('#curation-type-description').setValue('A new type')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('The name has already been taken.')

        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(window.axios.post).toHaveBeenLastCalledWith('/api/admin/curation-types', {
            name: 'New Curation Type',
            description: 'A new type',
        })
        expect(wrapper.text()).toContain('Curation type created successfully.')
        expect(wrapper.text()).toContain('New Curation Type')
    })
})
