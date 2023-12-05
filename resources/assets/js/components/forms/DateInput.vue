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
        value: {
            required: false,
            default: null
        }
    },
    emits: [
        'update:modelValue'
    ],
    data() {
        return {
            
        }
    },
    computed: {
        formattedDate () {
            if (!this.value) {
                return null;
            }
            const fmtdt = this.formatDate(this.value)
            console.log(fmtdt);
            return fmtdt
        }
    },
    methods: {
        setDate(event) {
            const date = new Date(Date.parse(event.target.value));
            const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset()*60*1000);

            this.$emit('input', adjustedDate)
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