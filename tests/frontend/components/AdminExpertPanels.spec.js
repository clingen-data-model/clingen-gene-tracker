import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminExpertPanels from '../../../resources/assets/js/components/admin/ExpertPanels.vue'

function userWith(...permissions) {
    return { hasPermission: permission => permissions.includes(permission) }
}

function mountPage(user = userWith()) {
    return mount(AdminExpertPanels, {
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
        get: vi.fn(url => Promise.resolve({ data: url === '/api/working-groups' ? [] : { data: [], total: 30 } })),
        post: vi.fn(),
        put: vi.fn(),
    }
})

describe('Expert Panel administration', () => {
    it('requests server pages when pagination changes', async () => {
        const wrapper = mountPage(userWith('list expert-panels'))
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/expert-panels', { params: { page: 1, per_page: 25 } })
        wrapper.findComponent({ name: 'BPagination' }).vm.$emit('update:modelValue', 2)
        await flushPromises()
        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/expert-panels', { params: { page: 2, per_page: 25 } })
    })

    it('renders relationships and counts with only permitted controls', async () => {
        window.axios.get.mockImplementation(url => Promise.resolve({ data: url === '/api/working-groups' ? [] : { data: [{
            id: 2,
            name: 'Known Expert Panel',
            working_group: { id: 3, name: 'Known Working Group' },
            affiliation: { name: 'Known Affiliation', clingen_id: '40001' },
            curations_count: 4,
            users_count: 5,
        }], total: 1 } }))
        const wrapper = mountPage(userWith('list expert-panels', 'update expert-panels'))
        await flushPromises()

        expect(wrapper.text()).toContain('Known Expert Panel')
        expect(wrapper.text()).toContain('Known Working Group')
        expect(wrapper.text()).toContain('Known Affiliation (40001)')
        expect(wrapper.text()).toContain('4')
        expect(wrapper.text()).toContain('5')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).not.toContain('Add Expert Panel')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('creates and updates name and Working Group without sending affiliation', async () => {
        window.axios.get.mockImplementation(url => Promise.resolve({ data: url === '/api/working-groups'
            ? [{ id: 7, name: 'Selectable Working Group' }]
            : { data: [], total: 0 } }))
        window.axios.post.mockResolvedValue({ data: {
            id: 8, name: 'Created Expert Panel', working_group_id: 7,
            working_group: { id: 7, name: 'Selectable Working Group' }, affiliation: null,
            curations_count: 0, users_count: 0,
        } })
        window.axios.put.mockResolvedValue({ data: {
            id: 8, name: 'Updated Expert Panel', working_group_id: null,
            working_group: null, affiliation: null, curations_count: 0, users_count: 0,
        } })
        const wrapper = mountPage(userWith('list expert-panels', 'create expert-panels', 'update expert-panels'))
        await flushPromises()

        await buttonByText(wrapper, 'Add Expert Panel').trigger('click')
        await wrapper.get('#expert-panel-name').setValue('Created Expert Panel')
        await wrapper.get('#expert-panel-working-group').setValue('7')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(window.axios.post).toHaveBeenCalledWith('/api/admin/expert-panels', {
            name: 'Created Expert Panel', working_group_id: 7,
        })

        await buttonByText(wrapper, 'Edit').trigger('click')
        expect(wrapper.text()).toContain('Affiliation linkage cannot be changed')
        await wrapper.get('#expert-panel-name').setValue('Updated Expert Panel')
        await wrapper.get('#expert-panel-working-group').setValue('')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/expert-panels/8', {
            name: 'Updated Expert Panel', working_group_id: null,
        })
    })

    it('shows backend validation feedback and exposes no destructive control', async () => {
        window.axios.post.mockRejectedValue({
            response: { status: 422, data: { errors: { name: ['The name field is required.'] } } },
        })
        const wrapper = mountPage(userWith('list expert-panels', 'create expert-panels', 'delete expert-panels'))
        await flushPromises()

        await buttonByText(wrapper, 'Add Expert Panel').trigger('click')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The name field is required.')
        expect(wrapper.text()).not.toContain('Delete')
    })
})
