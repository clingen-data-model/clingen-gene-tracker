import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createMemoryHistory, createRouter } from 'vue-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import AdminHome from '../../../resources/assets/js/components/admin/Home.vue'

async function mountPage() {
    const router = createRouter({ history: createMemoryHistory(), routes: [
        { path: '/', component: AdminHome },
        { path: '/report', name: 'admin-outdated-phenotypes', component: { template: '<div />' } },
    ] })
    await router.push('/')
    await router.isReady()
    return mount(AdminHome, { global: { plugins: [createBootstrap(), { install: registerBootstrapVueNext }, router] } })
}

beforeEach(() => {
    window.axios = { get: vi.fn().mockResolvedValue({ data: {
        outdated_phenotypes: 3,
        affected_curations: 2,
        outdated_phenotypes_in_use: 2,
    } }) }
})

describe('Administration dashboard', () => {
    it('renders historical counts and report destinations', async () => {
        const wrapper = await mountPage()
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/dashboard')
        expect(wrapper.text()).toContain('Outdated Phenotype Labels')
        expect(wrapper.text()).toContain('Affected Curations')
        expect(wrapper.text()).toContain('Outdated Phenotype Labels used on Curations')
        expect(wrapper.findAll('.h2').map(item => item.text())).toEqual(['3', '2', '2'])
        expect(wrapper.findAll('a').map(link => link.attributes('href'))).toEqual([
            '/report?tab=phenotypes', '/report?tab=curations', '/report?tab=phenotypes',
        ])
    })

    it('shows a useful API error', async () => {
        window.axios.get.mockRejectedValue({ response: { data: { message: 'Dashboard unavailable.' } } })
        const wrapper = await mountPage()
        await flushPromises()
        expect(wrapper.text()).toContain('Dashboard unavailable.')
    })
})
