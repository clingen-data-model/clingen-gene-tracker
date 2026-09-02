import { defineComponent, h, nextTick, onBeforeMount, ref } from 'vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import useCurationForm from '../../../resources/assets/js/composables/useCurationForm'

const Harness = defineComponent({
    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit, expose }) {
        const page = ref('phenotypes')
        const { updatedCuration } = useCurationForm(props, emit, page)

        expose({ updatedCuration, page })

        return () => null
    },
})

const StepNavigationHarness = defineComponent({
    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit, expose }) {
        const { updatedCuration } = useCurationForm(props, emit, 'phenotypes', {
            details: { note: 'default' },
        })

        onBeforeMount(() => {
            updatedCuration.value.details.note = 'default changed during mount'
        })

        expose({ updatedCuration })

        return () => null
    },
})

async function mountHarness(modelValue) {
    const wrapper = mount(Harness, { props: { modelValue } })
    await nextTick()
    return wrapper
}

describe('useCurationForm', () => {
    it('clones the initial model value, assigns the page, and does not mutate the parent', async () => {
        const parentValue = {
            id: 1,
            details: { note: 'original' },
        }
        const wrapper = await mountHarness(parentValue)

        expect(wrapper.vm.updatedCuration).toEqual({
            id: 1,
            details: { note: 'original' },
            page: 'phenotypes',
        })
        expect(wrapper.vm.updatedCuration).not.toBe(parentValue)
        expect(wrapper.vm.updatedCuration.details).not.toBe(parentValue.details)
        expect(parentValue).toEqual({ id: 1, details: { note: 'original' } })

        wrapper.unmount()
    })

    it('emits nested local edits without mutating the original parent input', async () => {
        const parentValue = {
            id: 1,
            details: { note: 'original' },
        }
        const wrapper = await mountHarness(parentValue)
        const initialEmitCount = wrapper.emitted('update:modelValue')?.length ?? 0

        wrapper.vm.updatedCuration.details.note = 'edited'
        await nextTick()

        const emittedValues = wrapper.emitted('update:modelValue')
        expect(emittedValues).toHaveLength(initialEmitCount + 1)
        expect(emittedValues.at(-1)[0].details.note).toBe('edited')
        expect(parentValue.details.note).toBe('original')

        wrapper.unmount()
    })

    it('synchronizes a genuinely new parent value into a fresh local clone', async () => {
        const wrapper = await mountHarness({ id: 1, details: { note: 'first' } })
        const nextParentValue = { id: 2, details: { note: 'second' } }

        await wrapper.setProps({ modelValue: nextParentValue })
        await nextTick()

        expect(wrapper.vm.updatedCuration).toEqual({
            id: 2,
            details: { note: 'second' },
            page: 'phenotypes',
        })
        expect(wrapper.vm.updatedCuration).not.toBe(nextParentValue)
        expect(nextParentValue).toEqual({ id: 2, details: { note: 'second' } })

        wrapper.unmount()
    })

    it('does not emit again when the parent echoes the emitted object back', async () => {
        const wrapper = await mountHarness({ id: 1, details: { note: 'first' } })

        wrapper.vm.updatedCuration.details.note = 'edited'
        await nextTick()

        const emittedValue = wrapper.emitted('update:modelValue')[0][0]
        const emitCount = wrapper.emitted('update:modelValue').length

        await wrapper.setProps({ modelValue: emittedValue })
        await nextTick()

        expect(wrapper.emitted('update:modelValue')).toHaveLength(emitCount)
        expect(wrapper.vm.updatedCuration).toBe(emittedValue)

        wrapper.unmount()
    })

    it('does not let queued default-state changes overwrite the populated parent during mounted synchronization', async () => {
        const populatedCuration = {
            id: 42,
            gene_symbol: 'E2E-BRAVO',
            details: { note: 'populated parent' },
        }
        const receivedValues = []

        const ParentHarness = defineComponent({
            setup(_, { expose }) {
                const parentCuration = ref(populatedCuration)
                const step = ref(null)

                function echoChildValue(value) {
                    receivedValues.push(JSON.parse(JSON.stringify(value)))
                    parentCuration.value = value
                }

                expose({ parentCuration, step })

                return () => h(StepNavigationHarness, {
                    ref: step,
                    modelValue: parentCuration.value,
                    'onUpdate:modelValue': echoChildValue,
                })
            },
        })

        const wrapper = mount(ParentHarness)
        await nextTick()

        expect(wrapper.vm.parentCuration).toMatchObject(populatedCuration)
        expect(wrapper.vm.step.updatedCuration).toEqual({
            ...populatedCuration,
            page: 'phenotypes',
        })
        expect(receivedValues.length).toBeGreaterThan(0)
        expect(receivedValues).not.toContainEqual({
            details: { note: 'default changed during mount' },
        })
        expect(receivedValues.every(value => value.id === populatedCuration.id)).toBe(true)

        wrapper.vm.step.updatedCuration.details.note = 'genuine local edit'
        await nextTick()

        expect(receivedValues.at(-1).details.note).toBe('genuine local edit')
        expect(wrapper.vm.parentCuration.details.note).toBe('genuine local edit')

        wrapper.unmount()
    })
})
