<style></style>
<template>
    <div>
        <b-form-group horizontal label="Mondo ID" label-for="mondo-id">
            <input type="text" v-model="updatedTopic.mondo_id" class="form-control"></input>
        </b-form-group>
        or
        <b-form-group horizontal label="Other entitiy">
            <input type="text" v-model="updatedTopic.other_entity" class="form-control"></input>
        </b-form-group>
    </div>
</template>
<script>
    export default {
        props: ['errors', 'value'],
        data: function () {
            return {
                updatedTopic: {}
            }
        },
        watch: {
            updatedTopic: function () {
                this.$emit('input', this.updatedTopic)
            },
            value: function () {
                this.updatedTopic = this.value
            }
        },
    }
</script>