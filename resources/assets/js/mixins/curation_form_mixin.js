export default {
    props: ['value', 'errors'],
    data() {
        return {
            updatedCuration: {
                ratonionales: []
            },
            page: null
        }
    },
    watch: {
        updatedCuration: function (to, from) {
            this.$emit('input', this.updatedCuration);
        },
        value: function () {
            if (this.value != this.updatedCuration) {
                this.syncValue();
            }
        }
    },
    methods: {
        syncValue() {
            if (this.value) {
                this.updatedCuration = JSON.parse(JSON.stringify(this.value));
                this.updatedCuration.page = this.page;
            }
        }
    },
    mounted() {
        this.syncValue();
    }
}