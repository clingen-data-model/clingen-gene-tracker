import { defineComponent, h } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { RouterView } from 'vue-router'
import router from '../../../resources/assets/js/routing'

const { getPage } = vi.hoisted(() => ({ getPage: vi.fn() }))
vi.mock('../../../resources/assets/js/resources/curations/get_page_of_curations', () => ({ default: getPage }))
vi.mock('../../../resources/assets/js/store/index', () => ({
    default: { getters: { getUser: { canAddCurations: () => false, isCurator: () => false } } },
}))

const TableStub = defineComponent({
    props: ['provider', 'fields', 'emptyText'],
    setup(_, { expose, slots }) {
        expose({ refresh: vi.fn() })
        return () => h('div', slots['cell(actions)']?.({ item: { id: 1, gene_symbol: 'LMNA' } }))
    },
})
let wrapper
async function open(path) {
    await router.push(path)
    wrapper = mount(RouterView, { global: {
        plugins: [router, createStore({ getters: { getUser: () => ({ canEditCuration: () => false, canDeleteCuration: () => false }) } })],
        stubs: { BTable: TableStub, BPagination: true, BBadge: true },
    } })
    await flushPromises()
}
async function request() {
    return wrapper.getComponent(TableStub).props('provider')({ currentPage: 1, perPage: 10, filter: null, sortBy: [] })
}
beforeEach(() => getPage.mockReset().mockResolvedValue({ data: { data: [], meta: { total: 0 } } }))
afterEach(() => wrapper?.unmount())

describe('gene curation routes', () => {
    it('initializes an exact gene scope and preserves multiple returned curations', async () => {
        const rows = [{ id: 1, gene_symbol: 'LMNA' }, { id: 2, gene_symbol: 'LMNA' }]
        getPage.mockResolvedValue({ data: { data: rows, meta: { total: 2 } } })
        await open('/genes/LMNA')
        expect(wrapper.get('h3').text()).toBe('Curations for LMNA')
        expect(wrapper.get('input[placeholder="Filter by Gene Symbol"]').element.value).toBe('LMNA')
        expect(await request()).toEqual(rows)
        const params = getPage.mock.calls.at(-1)[0]
        expect(params).toMatchObject({ gene_symbol: 'LMNA', filter: null, archive_filter: 'exclude', currentPage: 1 })
        expect(JSON.parse(params.filters).gene_symbol).toBe('')
        expect(wrapper.text()).toContain('Total Records: 2')
        expect(wrapper.find('#edit-curation-1-btn').exists()).toBe(false)
        expect(wrapper.find('#delete-curation-1-btn').exists()).toBe(false)
    })

    it('resets unrelated filters on parameter changes and on returning to all curations', async () => {
        await open('/genes/LMNA')
        await wrapper.get('input[placeholder="Filter by Expert Panel"]').setValue('unrelated')
        await wrapper.get('#archive-filter').setValue('only')
        await router.push('/genes/BRCA1')
        await flushPromises()
        await request()
        expect(getPage.mock.calls.at(-1)[0]).toMatchObject({ gene_symbol: 'BRCA1', archive_filter: 'exclude' })
        expect(JSON.parse(getPage.mock.calls.at(-1)[0].filters).expert_panel).toBe('')
        await router.push('/curations')
        await flushPromises()
        await request()
        expect(wrapper.get('h3').text()).toBe('All Curations')
        expect(getPage.mock.calls.at(-1)[0]).not.toHaveProperty('gene_symbol')
    })

    it('provides a gene-specific empty message and keeps the scope when clearing extra filters', async () => {
        await open('/genes/LMNA')
        await wrapper.findAll('button').find(button => button.text() === 'Clear').trigger('click')
        expect(await request()).toEqual([])
        expect(getPage.mock.calls.at(-1)[0].gene_symbol).toBe('LMNA')
        expect(wrapper.getComponent(TableStub).props('emptyText')).toBe('No curations found for LMNA.')
    })

    it.each(['/genes/%4CMNA', '/curations?gene=LMNA', '/genes/HLA-DRA', '/genes/MT-CO1'])('accepts decoded symbols at %s', async path => {
        await open(path)
        await request()
        expect(getPage.mock.calls.at(-1)[0].gene_symbol).toBe(path.includes('HLA') ? 'HLA-DRA' : path.includes('MT-') ? 'MT-CO1' : 'LMNA')
    })

    it.each(['/genes', '/genes/%25', '/genes/%252F', '/genes/LMNA%2CBRCA1', '/genes/LMNA%2FBRCA1', '/curations?gene=', '/curations?gene=LMNA&gene=BRCA1'])('rejects invalid input without fetching all curations: %s', async path => {
        await open(path)
        expect(wrapper.get('[role="alert"]').text()).toContain('valid gene symbol')
        expect(wrapper.findComponent(TableStub).exists()).toBe(false)
        expect(getPage).not.toHaveBeenCalled()
    })

    it('preserves the restricted dashboard redirect and existing detail route', async () => {
        await open('/')
        expect(router.currentRoute.value.path).toBe('/curations')
        expect(router.resolve('/curations/123').name).toBe('curations-show')
        expect(router.resolve('/curations/123/edit').name).toBe('curations-edit')
        expect(router.resolve('/bulk-lookup/curations').name).toBe('BulkCurationLookup')
    })
})
