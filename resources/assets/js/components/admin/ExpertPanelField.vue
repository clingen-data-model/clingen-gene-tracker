<style></style>

<template>
    <div>
        <input type="hidden" :value="serializedSelections"></input>
        <ul class="list-unstyled" v-if="selectedPanels.length > 0">
            <li v-for="panel in selectedPanels" v-bind:key="panel.id" class="form-group form-inline">
                <div  class="form-group form-inline">
                    <pre>{{updatedPanel}}</pre>
                    <select v-model="panel.expert_panel_id" class="form-control">
                        <option>Select...</option>
                        <option v-for="option in panelOptions" v-bind:key="option.id" :value="option.id">{{option.name}}</option>
                    </select>
                    <label>
                        <input type="checkbox" v-model="panel.is_curator">
                        Curator
                    </label>
                    <label>
                        <input type="checkbox" v-model="panel.is_coordinator">
                        Coordinator
                    </label>
                    <label>
                        <input type="checkbox" v-model="panel.can_edit_topics">
                        Can edit topics
                    </label>
                    <label>
                        <input type="checkbox" v-model="panel.can_create_topics">
                        Can create topics
                    </label>
                </div>
            </li>
        </ul>
        <div  class="form-group form-inline">
            <pre>{{panel}}</pre>
            <select v-model="newPanel.expert_panel_id" class="form-control">
                <option>Select...</option>
                <option v-for="option in panelOptions" v-bind:key="option.id" :value="option.id">{{option.name}}</option>
            </select>
            <label>
                <input type="checkbox" v-model="newPanel.is_curator">
                Curator
            </label>
            <label>
                <input type="checkbox" v-model="newPanel.is_coordinator">
                Coordinator
            </label>
            <label>
                <input type="checkbox" v-model="newPanel.can_edit_topics">
                Can edit topics
            </label>
            <label>
                <input type="checkbox" v-model="newPanel.can_create_topics">
                Can create topics
            </label>
        </div>
    </div>
</template>

<script>
    import UserPanelFieldset from './UserPanelFieldset'

    export default {
        components: {
            UserPanelFieldset
        },
        props: {
            connectedPanels: {
                required: true
            },
            panelOptions: {
                required: true
            }
        },
        data() {
            return {
                selectedPanels: [],
                newPanel: {}
            }
        },
        computed: {
            serializedSelections: function () {
                return JSON.stringify(this.selectedPanels);
            }
        },
        methods: {
            syncSelectedPanels() {
                this.selectedPanels = JSON.parse(JSON.stringify(this.connectedPanels))
            }
        },
        mounted() {
            this.syncSelectedPanels()
        }
    }
</script>