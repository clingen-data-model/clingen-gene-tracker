import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GeneBulkLookup from '../../../resources/assets/js/components/GeneBulkLookup.vue'

beforeEach(() => {
    window.axios = { get: vi.fn(), post: vi.fn() }
})

function mountLookup() {
    return mount(GeneBulkLookup, { global: { stubs: { LookupForm: true, BTable: true } } })
}

describe('OMIM download date', () => {
    it('renders the stored download date using the application date formatter', async () => {
        window.axios.get.mockResolvedValue({ data: { last_genemap_download: '2026-09-23 12:34:56' } })
        const wrapper = mountLookup()
        await flushPromises()
        expect(window.axios.get).toHaveBeenCalledExactlyOnceWith('/api/omim/genemap-status')
        expect(wrapper.text()).toContain('OMIM data last updated: September 23, 2026')
        expect(window.axios.post).not.toHaveBeenCalled()
        wrapper.unmount()
    })

    it.each([null, undefined, '', 'not-a-date', '2026-02-30 12:00:00'])(
        'shows Unknown for missing or invalid value %s', async value => {
            window.axios.get.mockResolvedValue({ data: { last_genemap_download: value } })
            const wrapper = mountLookup()
            await flushPromises()
            expect(wrapper.text()).toContain('OMIM data last updated: Unknown')
            wrapper.unmount()
        },
    )

    it('keeps the lookup available when metadata cannot be loaded', async () => {
        window.axios.get.mockRejectedValue(new Error('Unavailable'))
        const wrapper = mountLookup()
        await flushPromises()
        expect(wrapper.text()).toContain('OMIM data last updated: Unknown')
        expect(wrapper.findComponent({ name: 'LookupForm' }).exists()).toBe(true)
        wrapper.unmount()
    })
})
