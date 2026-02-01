<template>
    <DatePicker
        :modelValue="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        dateFormat="mm/dd/yy"
        :placeholder="placeholder"
        :disabled="readonly"
        showIcon
        class="w-100"
    />
</template>
<script>
    import DatePicker from 'primevue/datepicker'

    export default {
        name: 'date-field',
        components: { DatePicker },
        props: ['name', 'modelValue', 'id', 'placeholder', 'readonly'],
        emits: ['update:modelValue'],
    }
</script>
