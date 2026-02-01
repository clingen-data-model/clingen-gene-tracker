<template>
    <div>
        <input
            type="date"
            :value="formattedDate"
            class="form-control"
            @input="setDate"
        >
    </div>
</template>
<script>
export default {
    props: {
        modelValue: {
            required: false,
            default: null
        }
    },
    emits: ['update:modelValue'],
    computed: {
        formattedDate () {
            if (!this.modelValue) {
                return null;
            }
            return this.formatDate(this.modelValue)
        }
    },
    methods: {
        setDate(event) {
            const date = new Date(Date.parse(event.target.value));
            const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset()*60*1000);
            this.$emit('update:modelValue', adjustedDate)
        },
        formatDate(date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2)
                month = '0' + month;
            if (day.length < 2)
                day = '0' + day;

            return [year, month, day].join('-');
        }
    }
}
</script>
