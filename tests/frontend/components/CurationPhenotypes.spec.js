import { defineComponent, h } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Phenotypes from '../../../resources/assets/js/components/Curations/Forms/Phenotypes.vue'
import PhenotypeList from '../../../resources/assets/js/components/Curations/Phenotypes/List.vue'

const { forCuration } = vi.hoisted(() => ({ forCuration: vi.fn() }))
vi.mock('../../../resources/assets/js/repositories/OmimRepository', () => ({ default: { forCuration } }))

const outdated = { id: 1, name: 'Historical phenotype', mim_number: 123456, label_obsolete: true }
const current = { id: 2, name: 'Current phenotype', mim_number: 123456, label_obsolete: false }
const option = p => ({ id: p.id, phenotype: p.name, phenotypeMimNumber: p.mim_number, label_obsolete: p.label_obsolete })
const curation = selections => ({ id: 42, gene_symbol: 'LMNA', curation_type_id: 1, phenotypes: selections, rationales: [] })
const TableStub = defineComponent({
    props: ['items'],
    setup(props, { slots }) {
        return () => h('div', props.items.map(item => h('div', { key: item.id }, [
            slots['cell(phenotype)']({ item }), slots['cell(checkbox)']({ item }),
        ])))
    },
})

async function mountForm(selections = [outdated]) {
    const wrapper = mount(Phenotypes, {
        props: { modelValue: curation(selections), errors: {} },
        global: {
            plugins: [createStore({ getters: { loading: () => false, 'rationales/Items': () => [] } })],
            stubs: { BTable: TableStub, CriteriaTable: true, CurationNotifications: true, ValidationError: true },
        },
    })
    await flushPromises()
    return wrapper
}

beforeEach(() => forCuration.mockReset().mockResolvedValue({ data: { phenotypes: [option(outdated), option(current)] } }))

describe('curation phenotype selections', () => {
    it('shows historical rows without checkboxes and selects the current replacement by ID', async () => {
        const wrapper = await mountForm([{ ...outdated, extraApiProperty: true }])
        expect(wrapper.find('input[aria-label="Select Historical phenotype"]').exists()).toBe(false)
        expect(wrapper.get('input[aria-label="Select Current phenotype"]').element.checked).toBe(false)
        expect(wrapper.text()).toContain('Not in latest OMIM')
        await wrapper.get('input[aria-label="Select Current phenotype"]').setValue(true)
        expect(wrapper.emitted('update:modelValue').at(-1)[0].phenotypes).toEqual([current])
        expect(wrapper.text()).toContain('Historical phenotype')
        expect(wrapper.text()).toContain('Outdated phenotype associations will be removed when you save your phenotype selections.')
        wrapper.unmount()
        const reopened = await mountForm()
        expect(reopened.text()).toContain('Not in latest OMIM')
        expect(reopened.get('input[type="checkbox"]').element.checked).toBe(false)
        reopened.unmount()
    })

    it('drops removed options and the warning after reloading successfully saved selections', async () => {
        const wrapper = await mountForm()
        await wrapper.get('input[aria-label="Select Current phenotype"]').setValue(true)
        await wrapper.setProps({ modelValue: curation([current]) })
        forCuration.mockResolvedValue({ data: { phenotypes: [option(current)] } })
        await wrapper.vm.refreshPhenotypes()
        await flushPromises()
        expect(wrapper.text()).not.toContain('Historical phenotype')
        expect(wrapper.find('.alert-warning').exists()).toBe(false)
        expect(wrapper.get('input[type="checkbox"]').element.checked).toBe(true)
        wrapper.unmount()
        const refreshed = await mountForm([current])
        expect(refreshed.find('.alert-warning').exists()).toBe(false)
        expect(refreshed.text()).not.toContain('Not in latest OMIM')
        refreshed.unmount()
    })

    it('keeps historical selections when editing unrelated phenotype-page fields', async () => {
        const wrapper = await mountForm()
        await wrapper.get('#pmids').setValue('12345678')
        expect(wrapper.emitted('update:modelValue').at(-1)[0].phenotypes).toEqual([outdated])
        await wrapper.vm.refreshPhenotypes()
        expect(wrapper.text()).toContain('Not in latest OMIM')
        wrapper.unmount()
    })

    it('updates View immediately when curation associations change and preserves warnings for retained selections', async () => {
        const wrapper = mount(PhenotypeList, { props: { curation: curation([outdated, current]), geneSymbol: 'LMNA' } })
        expect(wrapper.text()).toContain('Not in latest OMIM')
        await wrapper.setProps({ curation: curation([outdated]) })
        expect(wrapper.text()).toContain('Not in latest OMIM')
        await wrapper.setProps({ curation: curation([current]) })
        expect(wrapper.text()).not.toContain('Historical phenotype')
        expect(wrapper.find('.alert-warning').exists()).toBe(false)
        await wrapper.setProps({ curation: curation([]) })
        expect(wrapper.text()).toContain('No phenotypes in this curation')
        wrapper.unmount()
    })
})
