import { flushPromises, mount } from '@vue/test-utils'
import { createBootstrap } from 'bootstrap-vue-next'
import { createMemoryHistory, createRouter } from 'vue-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import registerBootstrapVueNext from '../../../resources/assets/js/bootstrap-vue-next'
import Report from '../../../resources/assets/js/components/admin/OutdatedPhenotypes.vue'

async function mountPage(query = '') {
    const router = createRouter({ history: createMemoryHistory(), routes: [
        { path: '/report', name: 'admin-outdated-phenotypes', component: Report },
        { path: '/curations/:id', name: 'curations-show', component: { template: '<div />' } },
    ] })
    await router.push(`/report${query}`)
    await router.isReady()
    return { wrapper: mount(Report, { global: { plugins: [createBootstrap(), { install: registerBootstrapVueNext }, router] } }), router }
}

beforeEach(() => {
    window.axios = { get: vi.fn().mockResolvedValue({ data: { data: [], total: 0 } }) }
    URL.createObjectURL = vi.fn(() => 'blob:report')
    URL.revokeObjectURL = vi.fn()
    HTMLAnchorElement.prototype.click = vi.fn()
})

describe('Outdated phenotype reports', () => {
    it('renders phenotype rows and curation links', async () => {
        window.axios.get.mockResolvedValue({ data: { data: [{
            id: 3, mim_number: 600001, name: 'Outdated label', affected_curations: 1,
            curations: [{ id: 91, gene_symbol: 'REPORTGENE' }],
        }], total: 1 } })
        const { wrapper } = await mountPage('?tab=phenotypes')
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/reports/outdated-phenotypes', { params: { page: 1, per_page: 20 } })
        expect(wrapper.text()).toContain('600001')
        expect(wrapper.text()).toContain('Outdated label')
        expect(wrapper.text()).toContain('REPORTGENE #91')
    })

    it('keeps separate view state and loads curation report fields', async () => {
        window.axios.get.mockResolvedValue({ data: { data: [{
            id: 92, gene_symbol: 'CURATIONGENE', mondo_id: 'MONDO:0000001', mondo_name: 'Disease',
            expert_panel: { name: 'Known Panel' },
            phenotypes: [{ mim_number: 600002, name: 'Attached outdated label' }],
        }], total: 1 } })
        const { wrapper } = await mountPage('?tab=curations')
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/admin/reports/outdated-curations', { params: { page: 1, per_page: 20 } })
        expect(wrapper.text()).toContain('CURATIONGENE')
        expect(wrapper.text()).toContain('MONDO:0000001 — Disease')
        expect(wrapper.text()).toContain('Known Panel')
        expect(wrapper.text()).toContain('Attached outdated label (600002)')
    })

    it('requests the selected server page', async () => {
        window.axios.get.mockResolvedValue({ data: { data: [], total: 41 } })
        const { wrapper } = await mountPage('?tab=phenotypes')
        await flushPromises()
        await wrapper.findAll('button').find(button => button.text() === '2').trigger('click')
        await flushPromises()

        expect(window.axios.get).toHaveBeenLastCalledWith('/api/admin/reports/outdated-phenotypes', { params: { page: 2, per_page: 20 } })
    })

    it('downloads the complete active report through the authorized API', async () => {
        const blob = new Blob(['csv'])
        window.axios.get
            .mockResolvedValueOnce({ data: { data: [], total: 0 } })
            .mockResolvedValueOnce({ data: blob })
        const { wrapper } = await mountPage('?tab=curations')
        await flushPromises()
        await wrapper.get('button').trigger('click')
        await flushPromises()

        expect(window.axios.get).toHaveBeenLastCalledWith('/api/admin/reports/outdated-curations/export', { responseType: 'blob' })
        expect(URL.createObjectURL).toHaveBeenCalledWith(blob)
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:report')
    })
})
