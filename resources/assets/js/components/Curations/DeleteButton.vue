<style></style>

<template>
    <button 
        v-if="user.canDeleteCuration(curation)"
        :id="'delete-curation-'+curation.id+'-btn'"
        class="btn btn-danger"
        @click="deleteCuration(curation)"
    >
        <slot>Delete</slot>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'

const props = defineProps({
    curation: {
        required: true,
        type: Object
    }
})

const router = useRouter()
const store = useStore()
const user = computed(() => store.getters.getUser)
const title = computed(() => {
    let value = ''
    if (props.curation && props.curation.gene_symbol) {
        value += props.curation.gene_symbol
        if (props.curation.mondo_id) {
            value += ' / ' + props.curation.mondo_id
        }
        if (props.curation.expert_panel) {
            value += ' for ' + props.curation.expert_panel.name
        }
    }
    return value
})

function deleteCuration() {
    if (confirm("You're about to delete " + title.value + '. This can not be undone.  Are you sure you want to continue?')) {
        router.push('/')
        const curationTitle = title.value
        store.dispatch('curations/destroyItem', props.curation.id)
            .then(() => {
                store.commit('messages/addInfo', curationTitle + ' was successfully deleted.')
            })
            .catch((error) => {
                let msg = 'There was a problem deleting' + curationTitle
                if (error.response.status == 403) {
                    msg = 'You do not have permissions to delete curations.  Please contact an adminstrator to help you delete the curation.'
                }
                store.commit('messages/addError', msg)
            })
    }
}
</script>
