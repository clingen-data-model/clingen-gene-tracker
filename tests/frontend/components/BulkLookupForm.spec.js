import { defineComponent } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import LookupForm from '../../../resources/assets/js/components/Curations/BulkLookup/LookupForm.vue'

const TabsStub = defineComponent({
    props: ['index', 'vertical', 'pills', 'card'],
    emits: ['update:index'],
    template: '<div><slot /></div>',
})
const TabStub = defineComponent({ props: ['title'], template: '<section><slot /></section>' })

function mountForm() {
    return mount(LookupForm, {
        props: { modelValue: 'BRCA1' },
        global: { stubs: { BTabs: TabsStub, BTab: TabStub } },
    })
}

beforeEach(() => localStorage.clear())

describe('Bulk Lookup form navigation', () => {
    it('provides the original Manual entry and CSV Upload tabs with manual entry selected initially', () => {
        const wrapper = mountForm()
        expect(wrapper.findAllComponents(TabStub).map(tab => tab.props('title'))).toEqual(['Manual entry', 'CSV Upload'])
        expect(wrapper.getComponent(TabsStub).props('index')).toBe(0)
        wrapper.unmount()
    })

    it('restores and persists the selected input mode', async () => {
        localStorage.setItem('bulk-upload-form-tab', 'csv')
        const wrapper = mountForm()
        await flushPromises()
        expect(wrapper.getComponent(TabsStub).props('index')).toBe(1)
        wrapper.getComponent(TabsStub).vm.$emit('update:index', 0)
        await flushPromises()
        expect(localStorage.getItem('bulk-upload-form-tab')).toBe('manual')
        wrapper.unmount()
    })

    it('preserves the Vue 3 input, Clear, Search and CSV download contracts', async () => {
        const wrapper = mountForm()
        await wrapper.get('textarea').setValue('TP53')
        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['TP53'])
        const buttons = wrapper.findAll('button')
        await buttons.find(button => button.text() === 'Clear').trigger('click')
        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([''])
        await buttons.find(button => button.text() === 'Search').trigger('click')
        expect(wrapper.emitted('lookup')).toHaveLength(1)
        await buttons.find(button => button.text() === 'Get CSV').trigger('click')
        expect(wrapper.emitted('getCsv')).toHaveLength(1)
        wrapper.unmount()
    })
})
