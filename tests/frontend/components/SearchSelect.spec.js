import { nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import SearchSelect from '../../../resources/assets/js/components/forms/SearchSelect.vue'

const mountedWrappers = []

function mountSearchSelect(props = {}) {
    const wrapper = mount(SearchSelect, {
        props: {
            modelValue: null,
            options: ['Alpha', 'Beta', 'Gamma'],
            throttle: 250,
            ...props,
        },
        attachTo: document.body,
    })
    mountedWrappers.push(wrapper)
    return wrapper
}

async function runDebounce(milliseconds = 250) {
    await vi.advanceTimersByTimeAsync(milliseconds)
    await nextTick()
}

afterEach(() => {
    mountedWrappers.splice(0).forEach(wrapper => wrapper.unmount())
    vi.useRealTimers()
})

describe('SearchSelect', () => {
    it('filters local options after the debounce interval', async () => {
        vi.useFakeTimers()
        const wrapper = mountSearchSelect()

        await wrapper.find('input').setValue('a')
        expect(wrapper.findAll('.filtered-option')).toHaveLength(0)

        await runDebounce()

        expect(wrapper.findAll('.filtered-option').map(option => option.text())).toEqual([
            'Alpha',
            'Beta',
            'Gamma',
        ])
    })

    it('uses the asynchronous search function and passes the current options', async () => {
        vi.useFakeTimers()
        const searchFunction = vi.fn().mockResolvedValue(['Remote result'])
        const options = ['Local option']
        const wrapper = mountSearchSelect({ searchFunction, options })

        await wrapper.find('input').setValue('remote')
        await runDebounce()

        expect(searchFunction).toHaveBeenCalledOnce()
        expect(searchFunction).toHaveBeenCalledWith('remote', options)
        expect(wrapper.findAll('.filtered-option')).toHaveLength(1)
        expect(wrapper.find('.filtered-option').text()).toBe('Remote result')
    })

    it('coalesces rapid input into one search for the latest value', async () => {
        vi.useFakeTimers()
        const searchFunction = vi.fn().mockResolvedValue([])
        const wrapper = mountSearchSelect({ searchFunction })
        const input = wrapper.find('input')

        await input.setValue('a')
        await input.setValue('al')
        await input.setValue('alp')
        await runDebounce(249)
        expect(searchFunction).not.toHaveBeenCalled()

        await runDebounce(1)
        expect(searchFunction).toHaveBeenCalledOnce()
        expect(searchFunction).toHaveBeenCalledWith('alp', ['Alpha', 'Beta', 'Gamma'])
    })

    it('navigates results with arrow keys and keeps the cursor within bounds', async () => {
        vi.useFakeTimers()
        const wrapper = mountSearchSelect()
        const input = wrapper.find('input')

        await input.setValue('a')
        await runDebounce()

        expect(wrapper.findAll('.filtered-option')[0].classes()).toContain('highlighted')

        await input.trigger('keyup', { key: 'ArrowDown' })
        expect(wrapper.findAll('.filtered-option')[1].classes()).toContain('highlighted')

        await input.trigger('keyup', { key: 'ArrowDown' })
        await input.trigger('keyup', { key: 'ArrowDown' })
        expect(wrapper.findAll('.filtered-option')[2].classes()).toContain('highlighted')

        await input.trigger('keyup', { key: 'ArrowUp' })
        expect(wrapper.findAll('.filtered-option')[1].classes()).toContain('highlighted')

        await input.trigger('keyup', { key: 'ArrowUp' })
        await input.trigger('keyup', { key: 'ArrowUp' })
        expect(wrapper.findAll('.filtered-option')[0].classes()).toContain('highlighted')
    })

    it('selects the highlighted option with Enter', async () => {
        vi.useFakeTimers()
        const wrapper = mountSearchSelect()
        const input = wrapper.find('input')

        await input.setValue('a')
        await runDebounce()
        await input.trigger('keyup', { key: 'ArrowDown' })
        await input.trigger('keyup', { key: 'Enter' })

        expect(wrapper.emitted('update:modelValue')).toEqual([['Beta']])
        expect(wrapper.findAll('.filtered-option')).toHaveLength(0)
        expect(input.element.value).toBe('')
    })

    it('clears visible results with Escape', async () => {
        vi.useFakeTimers()
        const wrapper = mountSearchSelect()
        const input = wrapper.find('input')

        await input.setValue('a')
        await runDebounce()
        expect(wrapper.findAll('.filtered-option')).toHaveLength(3)

        await input.trigger('keyup', { key: 'Escape' })

        expect(wrapper.findAll('.filtered-option')).toHaveLength(0)
        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })

    it('emits null and focuses the input when removing a selection', async () => {
        const wrapper = mountSearchSelect({ modelValue: 'Alpha' })
        const input = wrapper.find('input')
        const focus = vi.spyOn(input.element, 'focus')

        await wrapper.find('.selection button').trigger('click')

        expect(wrapper.emitted('update:modelValue')).toEqual([[null]])
        expect(focus).toHaveBeenCalledOnce()
    })

    it('disables both searching and selection removal controls', async () => {
        const wrapper = mountSearchSelect({ modelValue: 'Alpha', disabled: true })
        const input = wrapper.find('input')
        const removeButton = wrapper.find('.selection button')

        expect(input.attributes('disabled')).toBeDefined()
        expect(removeButton.attributes('disabled')).toBeDefined()

        removeButton.element.click()
        await nextTick()

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })
})
