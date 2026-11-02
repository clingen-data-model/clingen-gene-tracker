import { describe, it, expect, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import Component from '../../../resources/assets/js/components/admin/GciReconciliation.vue'

describe('GCI information and review page', () => {
    it('searches IDs and UUIDs without exposing mutation workflows', async () => {
        const get = vi.fn().mockResolvedValue({ data: { data: [], current_page: 1, last_page: 1, total: 0 } })
        window.axios = { get, post: vi.fn() }
        const store = createStore({ getters: { getUser: () => ({ hasPermission: () => true }) } })
        const wrapper = mount(Component, { global: { plugins: [store], stubs: { RouterLink: true } } })
        await flushPromises()
        expect(wrapper.find('input[type=file]').exists()).toBe(false)
        expect(wrapper.text()).not.toMatch(/Create run|Preview selected|Confirm Apply|Download JSON/)
        await wrapper.get('#gt-id').setValue('123')
        await wrapper.get('#gdm-uuid').setValue('10000000-0000-4000-8000-000000000001')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(get).toHaveBeenLastCalledWith('/api/admin/gci-reconciliation/records', { params: expect.objectContaining({ curation_id: 123, gdm_uuid: '10000000-0000-4000-8000-000000000001' }) })
        expect(window.axios.post).not.toHaveBeenCalled()
        wrapper.unmount()
    })
    it('shows original and live values and saves only review decisions', async () => {
        const record = { id: 7, gci_gene_symbol: 'GENE', gci_mondo_id: 'MONDO:NEW', gt_mondo_id: 'MONDO:OLD',
            review_status: 'PENDING', current_matches_gci: true, current_gt: { curation_id: 123, mondo_id: 'MONDO:NEW' },
            current_comparison: { disease_match: true } }
        const get = vi.fn().mockImplementation(url => Promise.resolve({ data: url.endsWith('/7') ? { record, candidates: [] } : { data: [record], current_page: 1, last_page: 1, total: 1 } }))
        window.axios = { get, post: vi.fn().mockResolvedValue({}) }
        const store = createStore({ getters: { getUser: () => ({ hasPermission: () => true }) } })
        const wrapper = mount(Component, { global: { plugins: [store], stubs: { RouterLink: true } } })
        await flushPromises()
        await wrapper.findAll('button').find(b => b.text() === 'Inspect 7').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('GT at reconciliation')
        expect(wrapper.text()).toContain('MONDO:OLD')
        expect(wrapper.text()).toContain('MONDO:NEW')
        expect(wrapper.text()).toContain('Matches GCI now')
        await wrapper.get('#review-notes').setValue('Investigated using normal GT editor')
        await wrapper.findAll('form')[1].trigger('submit')
        await flushPromises()
        expect(window.axios.post).toHaveBeenCalledTimes(1)
        expect(window.axios.post).toHaveBeenCalledWith('/api/admin/gci-reconciliation/review', expect.objectContaining({ ids: [7], review_notes: 'Investigated using normal GT editor' }))
        wrapper.unmount()
    })

    it.each([
        ['PENDING', 'NOT_READY', 'Pending'],
        ['REVIEWED', 'APPLIED', 'Reviewed / Applied'],
        ['REVIEWED', 'NO_ACTION', 'Reviewed / No action'],
        ['REVIEWED', 'RESOLVED_MANUALLY', 'Reviewed / Resolved manually'],
        ['REVIEWED', 'NOT_READY', 'Reviewed / Not ready'],
    ])('displays %s + %s as %s', async (review_status, execution_status, label) => {
        const record = { id: 1, review_status, execution_status }
        window.axios = { get: vi.fn().mockResolvedValue({ data: { data: [record], current_page: 1, last_page: 1, total: 1 } }) }
        const store = createStore({ getters: { getUser: () => ({ hasPermission: () => true }) } })
        const wrapper = mount(Component, { global: { plugins: [store], stubs: { RouterLink: true } } })
        await flushPromises()
        expect(wrapper.get('tbody tr').findAll('td')[5].text()).toBe(label)
        wrapper.unmount()
    })

    it.each([
        ['DELETE_GCI_DATA', 'Delete GCI data'],
        ['MOVE_TO_PARKING_LOT', 'Move to parking lot'],
    ])('supports the %s review outcome and resolution filter', async (resolution, label) => {
        const record = { id: 1, review_status: 'REVIEWED', execution_status: 'NOT_READY', resolution }
        const get = vi.fn().mockResolvedValue({ data: { data: [record], current_page: 1, last_page: 1, total: 1 } })
        window.axios = { get }
        const store = createStore({ getters: { getUser: () => ({ hasPermission: () => true }) } })
        const wrapper = mount(Component, { global: { plugins: [store], stubs: { RouterLink: true } } })
        await flushPromises()
        expect(wrapper.get(`#review-resolution option[value="${resolution}"]`).text()).toBe(label)
        expect(wrapper.get('#review-resolution option[value="KEEP_DELETED"]').exists()).toBe(true)
        expect(wrapper.get('tbody tr').findAll('td')[5].text()).toBe(`Reviewed / ${label}`)
        await wrapper.get('#filter-resolution').setValue(resolution)
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(get).toHaveBeenLastCalledWith('/api/admin/gci-reconciliation/records', { params: expect.objectContaining({ resolution }) })
        wrapper.unmount()
    })

})
