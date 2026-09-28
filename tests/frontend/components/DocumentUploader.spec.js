import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, expect, it, vi } from 'vitest'
import DocumentUploader from '../../../resources/assets/js/components/Curations/Documents/DocumentUploader.vue'

describe('Document upload size information', () => {
    it('displays the backend-provided size in the file information section', async () => {
        window.axios = { get: vi.fn().mockResolvedValue({ data: { data: [] } }) }
        const wrapper = mount(DocumentUploader, {
            props: { curation: { id: 1 } },
            global: {
                plugins: [createStore({ getters: { getMaxUploadSize: () => '6 MB', getSupportedMimes: () => ['pdf', 'txt'] } })],
                stubs: { BModal: { template: '<div><slot /></div>' }, BCollapse: { template: '<div><slot /></div>' } },
                directives: { 'b-toggle': {} },
            },
        })
        await flushPromises()
        expect(wrapper.get('#file-info-collapse').text()).toContain('Max size: 6 MB')
        expect(wrapper.text()).toContain('Supported types: pdf, txt')
        wrapper.unmount()
    })
})
