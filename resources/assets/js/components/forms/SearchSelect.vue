<style scoped>
    .search-select-component {
        position: relative;
        overflow: visible;
        height: 2.5rem
    }

    .search-select-container {
        /* @apply border leading-6 px-2 flex flex-wrap py-1; */
        border: 1px solid;
        line-height: 1.5rem;
        padding: .5rem;
        display: flex;
        flex-wrap: wrap;
        padding: .25rem .5rem;
        border-radius: 10px;
    }

    .search-select-container > input {
        border: none;
    }
    
    .search-select-container > .selection {
        /* @apply bg-gray-500 text-white flex mr-1 mb-1 rounded-sm;*/
        margin: .15rem;
        border-radius: 5px;
        display: flex;
        background: #666;
        color: white;
    }

    .search-select-container > .selection.disabled {
        background: #aaa;
    }

    .search-select-container > .selection > * {
        /* @apply px-2 leading-6; */
        padding-left: .5rem;
        padding-right: .5rem;
        /* line-height: 1.5rem; */
    }

    .search-select-container > .selection > label {
        margin-bottom: 0;
    }

    .search-select-container > .selection > button {
        /* @apply border-l border-gray-400; */
        border-width: 0 0 0 1px;
        background-color: transparent;
        color: white;
    }
    
    .search-select-container .input {
        /* @apply border-none block outline-none focus:outline-none p-0 flex-1; */
        display: block;
        width: 100%;
        outline: none;
        padding: 0px;
        flex-grow: 1;
        z-index: 5
    }

    .result-container {
        position:relative;
    }

    .option-list {
        background: #efefef;
        box-shadow: 0 0 5px #666;
        list-style:none;
        margin: 0 .5rem;
        padding: 0;
        overflow: auto;
    }

    .filtered-option {
        /* @apply hover:bg-blue-200 cursor-pointer focus:bg-blue-200; */
        cursor:pointer;
        margin:0;
        padding: .25rem .5rem;
    }
    .filtered-option:hover {
        background-color: lightblue;
    }
    .filtered-option.highlighted {
        background-color: lightblue;
    } 
        /* 
*/
</style>

<template>
    <div class="search-select-component">
        <div class="search-select-container border">
            <div class="selection" :class="{disabled: disabled}" v-if="hasSelection">
                <label>
                    <slot name="selection-label" :selection="modelValue">
                        {{modelValue}}
                    </slot>
                </label>  
                <button @click="removeSelection()" :disabled="disabled">x</button>
            </div>
            <input 
                type="text" 
                v-model="searchText" 
                ref="input" 
                class="input" 
                v-show="showInput" 
                @keydown="startKeydownTimer"
                @keyup="handleKeyEvent"
                :placeholder="placeholder"
                :disabled="disabled"
            >
        </div>
        <div v-show="hasOptions" class="result-container">
            <ul class="option-list" :style="`max-height: ${optionsListHeight}px`">
                <li v-for="(opt, idx) in filteredOptions" 
                    :key="idx" 
                    class="filtered-option"
                    :class="{highlighted: (idx === cursorPosition)}"
                    :id="`option-${idx}`"
                    @click="setSelection(opt)"
                >
                    <slot :option="opt" :index="idx" name="option">{{opt}}</slot>
                </li>
            </ul>
        </div>
    </div>
</template>
<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { debounce } from 'lodash'

function inView(elem)
{
    const itemBounding = elem.getBoundingClientRect();

    if (document.getElementById('block')) {
        document.getElementById('block').remove();
    }
    const parentBounding = elem.parentNode.getBoundingClientRect();
    if (
        itemBounding.top >= parentBounding.top 
        && itemBounding.bottom <= parentBounding.bottom
    ) {
        return true;
    }

    return false;

}

const props = defineProps({
    throttle: {
        required: false,
        type: Number,
        default: 250,
    },
    searchFunction: {
        required: false,
        type: Function,
        default: null
    },
    modelValue: {
        required: true
    },
    options: {
        required: false,
        default: () => []
    },
    optionsHeight: {
        required: false,
        type: Number,
        default: 200
    },
    placeholder: {
        required: false,
        type: String,
        default: ''
    },
    disabled: {
        required: false,
        type: Boolean,
        default: false
    }
})
const emit = defineEmits(['update:modelValue'])

const input = ref(null)
const searchText = ref('')
const cursorPosition = ref(null)
const filteredOptions = ref([])
const keydownTimer = ref(null)
const currentKey = ref(null)

const hasOptions = computed(() => filteredOptions.value.length > 0)
const showingOptions = computed(() => filteredOptions.value.length > 0)
const optionsListHeight = computed(() => showingOptions.value ? props.optionsHeight : 0)
const hasSelection = computed(() => Boolean(props.modelValue))
const showInput = computed(() => !hasSelection.value)

const search = debounce(async (value, options) => {
    if (!props.searchFunction) {
        if (value === '') {
            return []
        }

        filteredOptions.value = options.filter(option => {
            const match = option.match(new RegExp(value, 'gi'))
            return match !== null
        })
        return
    }

    filteredOptions.value = await props.searchFunction(value, options)
}, props.throttle)

watch(searchText, () => {
    search(searchText.value, props.options)
})

watch(filteredOptions, () => {
    cursorPosition.value = 0
})

function removeSelection() {
    emit('update:modelValue', null)
    input.value.focus()
}

function setSelection(selection) {
    emit('update:modelValue', selection)
    clearInput()
    resetCursor()
}

function clearInput() {
    clearSearchText()
    clearOptions()
}

function clearOptions() {
    filteredOptions.value = []
}

function clearSearchText() {
    searchText.value = ''
}

function resetCursor() {
    cursorPosition.value = 0
}

function startKeydownTimer(evt) {
    if (evt.key == currentKey.value) {
        return
    }
    cancelKeydownTimer()
    if (evt.key == 'ArrowUp') {
        keydownTimer.value = setInterval(() => { moveUp() }, 100)
        currentKey.value = 'ArrowUp'
    }
    if (evt.key == 'ArrowDown') {
        keydownTimer.value = setInterval(() => { moveDown() }, 100)
        currentKey.value = 'ArrowDown'
    }
}

function cancelKeydownTimer() {
    if (keydownTimer.value) {
        clearInterval(keydownTimer.value)
        currentKey.value = null
    }
}

function moveUp() {
    if (!cursorPosition.value) {
        cursorPosition.value = 0
        return
    }
    if (cursorPosition.value - 1 < 0) {
        return
    }
    cursorPosition.value--
    scrollToHighlightedOption()
}

function moveDown() {
    if (cursorPosition.value === null) {
        cursorPosition.value = 0
        return
    }
    if (cursorPosition.value + 1 >= filteredOptions.value.length) {
        return
    }
    cursorPosition.value++
    scrollToHighlightedOption()
}

function handleKeyEvent(evt) {
    cancelKeydownTimer()
    if (showingOptions.value) {
        if (evt.key == 'ArrowDown') {
            moveDown()
        }
        if (evt.key == 'ArrowUp') {
            moveUp()
        }
        if (['Enter'].indexOf(evt.key) > -1) {
            evt.preventDefault()
            setSelection(filteredOptions.value[cursorPosition.value])
        }
        if (evt.key == 'Escape') {
            clearOptions()
        }
    }
}

function scrollToHighlightedOption() {
    const option = document.getElementById('option-' + cursorPosition.value)
    if (!inView(option)) {
        option.scrollIntoView()
    }
}

onUnmounted(() => {
    cancelKeydownTimer()
    search.cancel()
})
</script>
