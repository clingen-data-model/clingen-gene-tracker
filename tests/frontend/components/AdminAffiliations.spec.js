import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminAffiliations from '../../../resources/assets/js/components/admin/Affiliations.vue'

const affiliation = {
    id: 7,
    name: 'Canonical Affiliation',
    short_name: 'Local',
    clingen_id: 40001,
    type: { id: 3, name: 'gcep' },
    parent: { id: 2, name: 'Parent Affiliation' },
    expert_panel: { id: 9, name: 'Linked Expert Panel' },
    expert_panel_count: 1,
}

function mountPage(roles = []) {
    const user = { hasRole: role => roles.includes(role) }
    return mount(AdminAffiliations, {
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
        get: vi.fn().mockResolvedValue({ data: { data: [affiliation], total: 30 } }),
        put: vi.fn(),
    }
})

describe('Affiliation administration', () => {
    it('requests server pages when pagination changes', async () => {
        const wrapper = mountPage(['admin'])
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/affiliations', { params: { page: 1, per_page: 25 } })
        wrapper.findComponent({ name: 'BPagination' }).vm.$emit('update:modelValue', 2)
        await flushPromises()
        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/affiliations', { params: { page: 2, per_page: 25 } })
    })

    it('renders identity, hierarchy, type, and Expert Panel information as read-only', async () => {
        const wrapper = mountPage([])
        await flushPromises()

        expect(wrapper.text()).toContain('Canonical Affiliation')
        expect(wrapper.text()).toContain('Local')
        expect(wrapper.text()).toContain('40001')
        expect(wrapper.text()).toContain('gcep')
        expect(wrapper.text()).toContain('Parent Affiliation')
        expect(wrapper.text()).toContain('Linked Expert Panel')
        expect(wrapper.text()).not.toContain('Edit Short Name')
        expect(wrapper.text()).not.toContain('Add Affiliation')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('allows an administrator to update only the short name', async () => {
        window.axios.put.mockResolvedValue({ data: { ...affiliation, short_name: 'Updated' } })
        const wrapper = mountPage(['admin'])
        await flushPromises()

        await buttonByText(wrapper, 'Edit Short Name').trigger('click')
        expect(wrapper.text()).toContain('ClinGen identity, names, types, and hierarchy are synchronized externally')
        expect(wrapper.text()).toContain('Canonical Affiliation')
        await wrapper.get('#affiliation-short-name').setValue('Updated')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/affiliations/7', { short_name: 'Updated' })
        expect(wrapper.text()).toContain('Affiliation short name updated successfully.')
    })

    it('displays backend validation feedback without exposing identity inputs', async () => {
        window.axios.put.mockRejectedValue({
            response: { status: 422, data: { errors: { short_name: ['The short name is too long.'] } } },
        })
        const wrapper = mountPage(['programmer'])
        await flushPromises()

        await buttonByText(wrapper, 'Edit Short Name').trigger('click')
        expect(wrapper.find('input[name="clingen_id"]').exists()).toBe(false)
        expect(wrapper.find('select[name="affiliation_type_id"]').exists()).toBe(false)
        expect(wrapper.find('select[name="parent_id"]').exists()).toBe(false)
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('The short name is too long.')
    })
})
