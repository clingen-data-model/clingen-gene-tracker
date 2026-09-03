import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminMois from '../../../resources/assets/js/components/admin/Mois.vue'

function mountPage(permissions = []) {
    const user = { hasPermission: permission => permissions.includes(permission) }
    return mount(AdminMois, {
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

const moi = {
    id: 7,
    name: 'Autosomal dominant inheritance',
    abbreviation: 'AD',
    hp_id: 'HP:0000006',
    parent: { id: 1, name: 'Mode of inheritance' },
    curatable: 1,
}

beforeEach(() => {
    window.axios = {
        get: vi.fn().mockResolvedValue({ data: [moi] }),
        put: vi.fn(),
    }
})

describe('Mode of Inheritance administration', () => {
    it('renders canonical fields and hides update controls without permission', async () => {
        const wrapper = mountPage(['list mois'])
        await flushPromises()

        expect(wrapper.text()).toContain('Autosomal dominant inheritance')
        expect(wrapper.text()).toContain('AD')
        expect(wrapper.text()).toContain('HP:0000006')
        expect(wrapper.text()).toContain('Mode of inheritance')
        expect(wrapper.text()).toContain('Yes')
        expect(wrapper.text()).not.toContain('Edit Curatable')
        expect(wrapper.text()).not.toContain('Add Mode')
        expect(wrapper.text()).not.toContain('Delete')
    })

    it('edits only curatable and applies a successful response', async () => {
        window.axios.put.mockResolvedValue({ data: { ...moi, curatable: 0 } })
        const wrapper = mountPage(['list mois', 'update mois'])
        await flushPromises()

        await buttonByText(wrapper, 'Edit Curatable').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Canonical HPO fields are read-only')
        expect(wrapper.text()).toContain('Autosomal dominant inheritance')
        await wrapper.get('#moi-curatable').setValue('false')
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(window.axios.put).toHaveBeenCalledWith('/api/admin/mois/7', { curatable: false })
        expect(wrapper.text()).toContain('Mode of inheritance updated successfully.')
        expect(wrapper.text()).toContain('No')
    })

    it('displays backend validation feedback', async () => {
        window.axios.put.mockRejectedValue({
            response: { status: 422, data: { errors: { curatable: ['The curatable field is required.'] } } },
        })
        const wrapper = mountPage(['list mois', 'update mois'])
        await flushPromises()

        await buttonByText(wrapper, 'Edit Curatable').trigger('click')
        await flushPromises()
        await wrapper.get('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('The curatable field is required.')
    })
})
