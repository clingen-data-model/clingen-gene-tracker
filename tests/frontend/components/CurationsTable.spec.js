import { defineComponent, h, nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CurationsTable from '../../../resources/assets/js/components/Curations/Table.vue'

const { getPageOfCurations } = vi.hoisted(() => ({
    getPageOfCurations: vi.fn(),
}))

vi.mock('../../../resources/assets/js/resources/curations/get_page_of_curations', () => ({
    default: getPageOfCurations,
}))

const refresh = vi.fn()

const BTableStub = defineComponent({
    name: 'BTable',
    props: {
        provider: Function,
        fields: Array,
        filter: {
            default: null,
        },
        perPage: Number,
        currentPage: Number,
        sortBy: Array,
    },
    emits: ['sort-changed', 'update:sortBy'],
    setup(_, { expose }) {
        expose({ refresh })
        return () => h('div', { 'data-test': 'table' })
    },
})

const BPaginationStub = defineComponent({
    name: 'BPagination',
    props: {
        modelValue: Number,
        totalRows: Number,
        perPage: Number,
    },
    emits: ['update:modelValue'],
    setup() {
        return () => h('div', { 'data-test': 'pagination' })
    },
})

function mountTable(props = {}) {
    const store = createStore({
        getters: {
            getUser: () => ({ canEditCuration: () => false }),
        },
    })

    return mount(CurationsTable, {
        props,
        global: {
            plugins: [store],
            stubs: {
                BTable: BTableStub,
                BPagination: BPaginationStub,
                BBadge: true,
                RouterLink: true,
                DeleteButton: true,
                Transition: false,
            },
        },
    })
}

beforeEach(() => {
    getPageOfCurations.mockReset()
    refresh.mockReset()
})

describe('Curations/Table provider contract', () => {
    it('adapts provider state to the backend contract and updates the total row count', async () => {
        const rows = [{ id: 1, gene_symbol: 'BRCA1' }]
        getPageOfCurations.mockResolvedValue({
            data: { data: rows, meta: { total: 37 } },
        })
        const wrapper = mountTable({
            sortBy: 'gene_symbol',
            pageLength: 25,
            searchParams: {
                working_group_id: 9,
                perPage: 50,
                sortBy: 'id',
                archive_filter: 'only',
            },
        })

        await wrapper.find('button').trigger('click')
        const advancedInputs = wrapper.findAll('.toolbar-filters input')
        await advancedInputs[0].setValue('BRCA')
        await wrapper.find('#archive-filter').setValue('include')
        await nextTick()
        refresh.mockClear()

        const provider = wrapper.findComponent(BTableStub).props('provider')
        const result = await provider({
            currentPage: 3,
            perPage: 25,
            filter: 'curator search',
            sortBy: [{ key: 'gene_symbol', order: 'desc' }],
        })

        expect(getPageOfCurations).toHaveBeenCalledOnce()
        expect(getPageOfCurations).toHaveBeenCalledWith({
            currentPage: 3,
            perPage: 50,
            filter: 'curator search',
            sortBy: 'id',
            sortDesc: true,
            working_group_id: 9,
            filters: JSON.stringify({
                gene_symbol: 'BRCA',
                mode_of_inheritance: '',
                mondo_id: '',
                expert_panel: '',
                curator: '',
                current_status: '',
                gdm_uuid_status: '',
                id: '',
            }),
            archive_filter: 'include',
        })
        expect(result).toBe(rows)
        expect(wrapper.text()).toContain('Total Records: 37')

        wrapper.unmount()
    })

    it('uses the configured default sort when the provider has no sort model', async () => {
        getPageOfCurations.mockResolvedValue({
            data: { data: [], meta: { total: 0 } },
        })
        const wrapper = mountTable({ sortBy: 'mondo_id', pageLength: 10 })
        const provider = wrapper.findComponent(BTableStub).props('provider')

        await provider({ currentPage: 1, perPage: 10, filter: null, sortBy: [] })

        expect(getPageOfCurations).toHaveBeenCalledWith(expect.objectContaining({
            currentPage: 1,
            perPage: 10,
            filter: null,
            sortBy: 'mondo_id',
            sortDesc: false,
            archive_filter: 'exclude',
        }))
        wrapper.unmount()
    })

    it('resets the page on sorting and filter changes', async () => {
        const wrapper = mountTable()
        const table = wrapper.findComponent(BTableStub)
        const pagination = wrapper.findComponent(BPaginationStub)

        pagination.vm.$emit('update:modelValue', 4)
        await nextTick()
        expect(table.props('currentPage')).toBe(4)

        table.vm.$emit('sort-changed', {})
        await nextTick()
        expect(table.props('currentPage')).toBe(1)

        pagination.vm.$emit('update:modelValue', 3)
        await nextTick()
        await wrapper.find('input[placeholder^="Search curations"]').setValue('BRCA')
        await nextTick()
        expect(table.props('currentPage')).toBe(1)

        wrapper.unmount()
    })

    it('coalesces synchronous filter refresh triggers into one explicit table refresh', async () => {
        const wrapper = mountTable()
        const table = wrapper.findComponent(BTableStub)
        const pagination = wrapper.findComponent(BPaginationStub)

        pagination.vm.$emit('update:modelValue', 3)
        await nextTick()

        const searchInput = wrapper.find('input[placeholder^="Search curations"]')
        const archiveInput = wrapper.find('#archive-filter')
        searchInput.element.value = 'BRCA'
        searchInput.element.dispatchEvent(new Event('input'))
        archiveInput.element.value = 'only'
        archiveInput.element.dispatchEvent(new Event('change'))

        await nextTick()
        await nextTick()

        expect(table.props('currentPage')).toBe(1)
        expect(refresh).toHaveBeenCalledOnce()

        wrapper.unmount()
    })

    it.each(['exclude', 'include', 'only'])('sends the %s archive state to the provider', async state => {
        getPageOfCurations.mockResolvedValue({ data: { data: [], meta: { total: 0 } } })
        const wrapper = mountTable()
        await wrapper.get('#archive-filter').setValue(state)
        await wrapper.getComponent(BTableStub).props('provider')({ currentPage: 1, perPage: 10, sortBy: [] })
        expect(getPageOfCurations).toHaveBeenLastCalledWith(expect.objectContaining({ archive_filter: state }))
        wrapper.unmount()
    })

    it('sends GCI UUID status and clears it along with archive and keyword filters', async () => {
        getPageOfCurations.mockResolvedValue({ data: { data: [], meta: { total: 0 } } })
        const wrapper = mountTable()
        await wrapper.find('button').trigger('click')
        const gciStatus = wrapper.findAll('.toolbar-filters select').find(select => select.text().includes('Has GCI UUID'))
        const provider = wrapper.getComponent(BTableStub).props('provider')
        for (const status of ['has', 'none']) {
            await gciStatus.setValue(status)
            await provider({ currentPage: 1, perPage: 10, sortBy: [] })
            expect(JSON.parse(getPageOfCurations.mock.calls.at(-1)[0].filters).gdm_uuid_status).toBe(status)
        }
        await wrapper.get('#archive-filter').setValue('only')
        await wrapper.get('input[placeholder^="Search curations"]').setValue('10000000-0000-4000-8000-000000009103')
        await wrapper.findAll('button').find(button => button.text() === 'Clear').trigger('click')
        const table = wrapper.getComponent(BTableStub)
        await provider({ currentPage: table.props('currentPage'), perPage: 10, filter: table.props('filter'), sortBy: [] })
        const request = getPageOfCurations.mock.calls.at(-1)[0]
        expect(request).toMatchObject({ currentPage: 1, filter: null, archive_filter: 'exclude' })
        expect(Object.values(JSON.parse(request.filters)).every(value => value === '')).toBe(true)
        expect(wrapper.get('#archive-filter').element.value).toBe('exclude')
        wrapper.unmount()
    })
})
