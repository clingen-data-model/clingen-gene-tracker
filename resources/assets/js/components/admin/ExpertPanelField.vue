<style></style>

<template>
    <div>
        <!-- <pre>{{selectedPanels.length}}</pre> -->
        <input type="hidden" :value="serializedSelections" name="expert_panels_json">
        <table 
            class="table table-striped" 
        >
            <tr v-if="selectedPanels.length > 0">
                <th><small>Expert Panel</small></th>
                <th class="text-center"><small>Curator</small></th>
                <th class="text-center"><small>Coordinator</small></th>
                <th class="text-center"><small>Edit Curations</small></th>
                <!-- <th class="text-center"><small>Add Curations</small></th> -->
                <th class="text-center"></th>
            </tr>
            <tr v-for="(panel, idx) in selectedPanels" v-bind:key="panel.id" class="text-center">
                <td>
                    <small>
                        <select v-model="panel.id" class="form-control">
                            <option>Select...</option>
                            <option v-for="option in panelOptions" v-bind:key="option.id" :value="option.id">{{option.name}}</option>
                        </select>
                    </small>
                </td>
                <td>
                    <input type="checkbox" v-model="panel.pivot.is_curator">
                </td>
                <td>
                    <input type="checkbox" v-model="panel.pivot.is_coordinator">
                </td>
                <td>
                    <input type="checkbox" v-model="panel.pivot.can_edit_curations">
                </td>
                <!-- <td>
                    <input type="checkbox" v-model="panel.pivot.can_create_curations">
                </td> -->
                <td>
                    <button type="button" class="btn btn-sm btn-danger" @click="removePanel(idx)">
                        <span class="la la-times"></span>
                    </button>
                </td>
            </tr>
            <tr>
                <td style="padding: .5em 0 .5em 0">
                    <strong>
                        <button 
                            @click="addNewPanel()"
                            type="button" 
                            class="btn btn-sm btn-primary">
                            Add expert panel
                        </button>
                    </strong>
                </td>
            </tr>
        </table>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    connectedPanels: {
        required: true,
        default: []
    },
    panelOptions: {
        required: true
    }
})

const selectedPanels = ref([])

const serializedSelections = computed(() => JSON.stringify(selectedPanels.value))

function syncSelectedPanels() {
    selectedPanels.value = JSON.parse(JSON.stringify(props.connectedPanels))
    selectedPanels.value.map((panel) => {
        panel.pivot.is_curator = (panel.pivot.is_curator == 1)
        panel.pivot.is_coordinator = (panel.pivot.is_coordinator == 1)
        panel.pivot.can_edit_curations = (panel.pivot.can_edit_curations == 1)
        return panel
    })
}

function addNewPanel() {
    selectedPanels.value.push({
        pivot: {}
    })
}

function removePanel(idx) {
    selectedPanels.value.splice(idx, 1)
}

onMounted(() => {
    syncSelectedPanels()
})
</script>
