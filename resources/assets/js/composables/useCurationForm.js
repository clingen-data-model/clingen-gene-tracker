import { onMounted, ref, toRaw, unref, watch } from 'vue'

export default function useCurationForm(props, emit, page) {
    const updatedCuration = ref({
        gene_symbol: null,
        ratonionales: []
    })

    let lastEmittedValue = null

    function syncValue() {
        if (props.modelValue) {
            const clonedCuration = JSON.parse(JSON.stringify(props.modelValue))
            clonedCuration.page = unref(page)
            updatedCuration.value = clonedCuration
        }
    }

    watch(updatedCuration, (value) => {
        lastEmittedValue = toRaw(value)
        emit('update:modelValue', value)
    }, { deep: true })

    watch(() => props.modelValue, (value) => {
        if (
            toRaw(value) !== toRaw(updatedCuration.value)
            && toRaw(value) !== lastEmittedValue
        ) {
            syncValue()
        }
    })

    onMounted(syncValue)

    return {
        updatedCuration,
        syncValue
    }
}
