import { onMounted, ref, toRaw, unref, watch } from 'vue'

export default function useCurationForm(props, emit, page, initialValue = {
    gene_symbol: null,
    ratonionales: []
}) {
    const updatedCuration = ref(initialValue)

    let lastEmittedValue = null
    let hydrated = false

    function syncValue() {
        if (props.modelValue) {
            const clonedCuration = JSON.parse(JSON.stringify(props.modelValue))
            clonedCuration.page = unref(page)
            updatedCuration.value = clonedCuration
        }
    }

    watch(updatedCuration, (value) => {
        if (!hydrated) {
            return
        }
        lastEmittedValue = toRaw(value)
        emit('update:modelValue', value)
    }, { deep: true, flush: 'post' })

    watch(() => props.modelValue, (value) => {
        if (
            toRaw(value) !== toRaw(updatedCuration.value)
            && toRaw(value) !== lastEmittedValue
        ) {
            syncValue()
        }
    })

    onMounted(() => {
        syncValue()
        hydrated = true
    })

    return {
        updatedCuration,
        syncValue
    }
}
