<style>
.form-control[readonly] {
    background: #fff;
}
</style>
<template>
    <DatePicker :id="id" v-model="date" :name="name" :placeholder="placeholder" :readonly="readonly"
        class="form-control" v-on:input="$emit('input', $event)" showIcon iconDisplay="input" :date-format="mm / dd / yy" />
</template>
<script setup>
import DatePicker from 'primevue/datepicker'
import { computed } from 'vue';

defineProps({
    name: {
        type: String,
        required: true
    },
    value: {
        type: [Date, String],
        default: null
    },
    id: {
        type: String,
        default: null
    },
    placeholder: {
        type: String,
        default: 'Select Date'
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

</script>