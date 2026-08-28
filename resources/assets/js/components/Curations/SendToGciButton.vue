<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import moment from 'moment'
import queryStringFromParams from '../../http/query_string_from_params'

const props = defineProps({
    curation: {
        type: Object,
        required: true
    }
})
const emit = defineEmits(['saved'])
const store = useStore()

const sendToGciEnabled = computed(() => store.state.features.sendToGciEnabled)
const enabled = computed(() => {
    return props.curation.hgnc_id
        && props.curation.disease
        && props.curation.mode_of_inheritance
        && !props.curation.gdm_uuid
})
const popoverText = computed(() => {
    if (!enabled.value) {
        const reason = props.curation.gdm_uuid
            ? 'the curation is already associatd with a GCI record.'
            : ' the curation is not complete.'
        return `Disabled because ${reason}`
    }
    return null
})

async function handleClick() {
    await store.dispatch('curations/storeItemUpdates', props.curation)
    await store.dispatch('curations/linkNewStatus', {
        curation: props.curation,
        data: {
            curation_status_id: 4,
            status_date: moment().format('YYYY-MM-DD')
        }
    })
    emit('saved')
    redirectToGciCreationForm()
}

function redirectToGciCreationForm() {
    const params = {
        aff: props.curation.expert_panel.affiliation.clingen_id,
        gtid: props.curation.uuid,
        gene: props.curation.gene_symbol,
        disease: props.curation.mondo_id,
        moi: props.curation.mode_of_inheritance.hp_id
    }

    const url = `https://curation.clinicalgenome.org/create-gene-disease${queryStringFromParams(params)}`
    window.open(url, '_gci')
}
</script>

<template>
    <div v-if="sendToGciEnabled">
        <span id="send-to-gci-button">            
            <button class="btn btn-primary btn-lg" 
                :disabled="!enabled" 
                @click="handleClick"
                :title="popoverText"
            >
                Complete PreCuration and Go to GCI
            </button>
        </span>
        <b-popover target="send-to-gci-button" triggers="hover" placement="top" v-if="!enabled">
            {{popoverText}}
        </b-popover>
    </div>
</template>
