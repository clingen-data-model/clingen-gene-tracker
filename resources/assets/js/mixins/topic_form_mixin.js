export default {
    props: ['value', 'errors'],
    data() {
        return {
            updatedTopic: {},
            page: null
        }
    },
    watch: {
        updatedTopic: function (to, from) {
            this.$emit('input', this.updatedTopic);
        },
        value: function () {
            if (this.value != this.updatedTopic) {
                this.syncValue();
            }
        }
    },
    methods: {
        syncValue() {
            // console.log('topic_form_mixin.syncValue')
            if (this.value) {
                this.updatedTopic = JSON.parse(JSON.stringify(this.value));
                this.updatedTopic.page = this.page;
                console.log("topic's page: "+this.updatedTopic.page)
            }
        }
    },
    mounted() {
        this.syncValue();
    }
}