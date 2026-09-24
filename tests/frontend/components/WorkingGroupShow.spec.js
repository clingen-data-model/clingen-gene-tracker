import { defineComponent } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, expect, it, vi } from 'vitest'
import WorkingGroupShow from '../../../resources/assets/js/components/WorkingGroups/Show.vue'

vi.mock('vue-router', () => ({ onBeforeRouteUpdate: vi.fn() }))

const TabsStub = defineComponent({
    props: ['index'],
    emits: ['update:index'],
    template: '<div><slot /></div>',
})

function mountGroup() {
    let finishLoading
    const store = createStore({
        modules: {
            workingGroups: {
                namespaced: true,
                state: () => ({ items: [] }),
                getters: {
                    Items: state => state.items,
                    getItemById: state => id => state.items.find(group => group.id === id) || {},
                },
                actions: {
                    fetchItem: () => new Promise(resolve => { finishLoading = resolve }),
                },
            },
        },
    })
    const wrapper = mount(WorkingGroupShow, {
        props: { id: 1 },
        global: {
            plugins: [store],
            stubs: {
                RouterLink: { template: '<a><slot /></a>' },
                BTabs: TabsStub,
                BTab: { template: '<div><slot /></div>' },
                ExpertPanelTabs: true,
            },
        },
    })
    async function load(panels) {
        store.state.workingGroups.items = [{ id: 1, name: 'Test Group', expert_panels: panels }]
        finishLoading()
        await flushPromises()
    }
    return { wrapper, load }
}

describe('Working Group panel selection', () => {
    it('selects the first panel after asynchronous loading and preserves subsequent selection', async () => {
        const { wrapper, load } = mountGroup()
        expect(wrapper.findComponent(TabsStub).exists()).toBe(false)

        const panels = [{ id: 10, name: 'First' }, { id: 20, name: 'Second' }]
        await load(panels)
        const tabs = wrapper.getComponent(TabsStub)
        expect(tabs.props('index')).toBe(0)
        expect(wrapper.findAllComponents({ name: 'ExpertPanelTabs' })[0].props('expertPanel')).toEqual(panels[0])

        tabs.vm.$emit('update:index', 1)
        await load([...panels])
        expect(wrapper.getComponent(TabsStub).props('index')).toBe(1)
        wrapper.unmount()
    })

    it('keeps the empty state when the group has no panels', async () => {
        const { wrapper, load } = mountGroup()
        await load([])
        expect(wrapper.findComponent(TabsStub).exists()).toBe(false)
        expect(wrapper.findAll('.alert').find(alert => alert.text().includes('does not have')).isVisible()).toBe(true)
        wrapper.unmount()
    })
})
