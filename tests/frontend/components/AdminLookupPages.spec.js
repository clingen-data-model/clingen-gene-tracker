import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import CurationStatuses from '../../../resources/assets/js/components/admin/CurationStatuses.vue'
import Rationales from '../../../resources/assets/js/components/admin/Rationales.vue'
import UploadCategories from '../../../resources/assets/js/components/admin/UploadCategories.vue'

function userWith(...permissions) {
    return { hasPermission: permission => permissions.includes(permission) }
}

function mountPage(component, user = userWith()) {
    return mount(component, {
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
        get: vi.fn().mockResolvedValue({ data: [] }),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
    window.confirm = vi.fn(() => true)
})

describe('Rationales administration', () => {
    it('renders data and only the permitted operations', async () => {
        window.axios.get.mockResolvedValue({ data: [{ id: 1, name: 'Known Rationale' }] })
        const wrapper = mountPage(Rationales, userWith('list rationales', 'update rationales'))
        await flushPromises()

        expect(wrapper.text()).toContain('Known Rationale')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).not.toContain('Add Rationale')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('displays backend validation during creation', async () => {
        window.axios.post.mockRejectedValue({
            response: { status: 422, data: { errors: { name: ['The name field is required.'] } } },
        })
        const wrapper = mountPage(Rationales, userWith('list rationales', 'create rationales'))
        await flushPromises()

        await wrapper.get('button').trigger('click')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('The name field is required.')
    })
})

describe('Curation Statuses administration', () => {
    it('renders name and description with permission-sensitive actions', async () => {
        window.axios.get.mockResolvedValue({ data: [{ id: 1, name: 'Uploaded', description: 'Initial status' }] })
        const wrapper = mountPage(CurationStatuses, userWith(
            'list curation-statuses',
            'create curation-statuses',
            'delete curation-statuses',
        ))
        await flushPromises()

        expect(wrapper.text()).toContain('Uploaded')
        expect(wrapper.text()).toContain('Initial status')
        expect(wrapper.text()).toContain('Add Curation Status')
        expect(wrapper.text()).toContain('Delete')
        expect(wrapper.text()).not.toContain('Edit')
    })

    it('displays backend validation during creation', async () => {
        window.axios.post.mockRejectedValue({
            response: { status: 422, data: { errors: { name: ['The name has already been taken.'] } } },
        })
        const wrapper = mountPage(CurationStatuses, userWith('list curation-statuses', 'create curation-statuses'))
        await flushPromises()

        await wrapper.get('button').trigger('click')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('The name has already been taken.')
    })
})

describe('Upload Categories administration', () => {
    it('renders role-authorized CRUD controls and validation feedback without operation permissions', async () => {
        window.axios.get.mockResolvedValue({ data: [{ id: 1, name: 'Evidence' }] })
        window.axios.post.mockRejectedValue({
            response: { status: 422, data: { errors: { name: ['The name field is required.'] } } },
        })
        const wrapper = mountPage(UploadCategories)
        await flushPromises()

        expect(wrapper.text()).toContain('Evidence')
        expect(wrapper.text()).toContain('Add Upload Category')
        expect(wrapper.text()).toContain('Edit')
        expect(wrapper.text()).toContain('Delete')

        await wrapper.get('button').trigger('click')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The name field is required.')
    })
})
