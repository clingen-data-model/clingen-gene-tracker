<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id">
            <input type="text" v-model="updatedTopic.mondo_id" class="form-control" placeholder="MONDO:0001158"></input>
            <small class="text-muted">Refer to <a href="https://www.ebi.ac.uk/ols/ontologies/mondo" target="mondo">MonDO</a> for a valid MonDO ID</small>
        </b-form-group>
        or
        <b-form-group horizontal label="Disease Entity">
            <textarea v-model="updatedTopic.disease_entity_notes" class="form-control"></textarea>
            <small>Use when no appropriate MonDO ID is available</small>
        </b-form-group>
        
    </div>
</template>
<script>
    import topicFormMixin from '../../../mixins/topic_form_mixin'

    export default {
        mixins: [
            topicFormMixin // handles syncing of prop value to updatedTopic
        ],
        data: function () {
            return {
                page: 'mondo',
                updatedTopic: {}
            }
        },
        watch: {
            updatedTopic: function () {
                this.$emit('input', this.updatedTopic)
            },
            value: function (to, from) {
                if (this.value != this.updatedTopic) {
                    this.syncValue();
                }
            }
        }
    }
</script>