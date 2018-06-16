<style></style>

<template>
    <div  class="form-group form-inline">
        <pre>{{updatedPanel}}</pre>
        <select v-model="updatedPanel.expert_panel_id" class="form-control">
            <option>Select...</option>
            <option v-for="option in panelOptions" v-bind:key="option.id" :value="option.id">{{option.name}}</option>
        </select>
        <label>
            <input type="checkbox" v-model="updatedPanel.is_curator">
            Curator
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.is_coordinator">
            Coordinator
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.can_edit_topics">
            Can edit topics
        </label>
        <label>
            <input type="checkbox" v-model="updatedPanel.can_edit_topics">
            Can create topics
        </label>
    </div>
</template>

<script>
export default {
    props: [
        'panel',
        'panelOptions'
    ],
    data() {
        return {
            updatedPanel: {}
        }
    },
    watch: {
        updatedPanel: function() {
            this.$emit('updated', this.updatedPanel)
        }
    },
    mount() {
        this.updatedPanel = this.panel
    }
}
</script>