import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import GciUpload from '../../../resources/assets/js/components/admin/GciUpload.vue'

describe('GciUpload', () => {
    it('submits the explicitly selected source type with the file', async () => {
        const wrapper = mount(GciUpload)
        await wrapper.get('select').setValue('NO_PRECURATION_ID')
        const input = wrapper.get('input[type="file"]')
        const file = new File(['{"Items":[]}'], 'arbitrary-name.json', { type: 'application/json' })
        Object.defineProperty(input.element, 'files', { value: [file] })
        await input.trigger('change')
        await wrapper.get('form').trigger('submit')
        const payload = wrapper.emitted('upload')[0][0]
        expect(payload.get('source_type')).toBe('NO_PRECURATION_ID')
        expect(payload.get('file').name).toBe('arbitrary-name.json')
    })
    it('disables upload while the parent is busy', () => {
        const wrapper = mount(GciUpload, { props: { disabled: true } })
        expect(wrapper.get('button').element.disabled).toBe(true)
        expect(wrapper.get('select').element.disabled).toBe(true)
    })
})
