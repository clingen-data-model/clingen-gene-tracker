<style></style>
<template>
    <div class="new-curation-container">
        <div v-if="!user.canAddCurations()" class="alert alert-danger">
            Sorry.  You don't have permission to create curations.
        </div>
        <div v-else>
            <p>
                <router-link to="/curations">
                        &lt; Back to curations
                </router-link>
            </p>        
            <b-card>
                <template #header>
                    <h3>Add a curation to curate</h3>
                </template>
                <b-form id="new-curation-form">
                    <info
                        :model-value="updatedCuration" 
                        :errors="errors"
                        @update:model-value="updatedCuration = $event"
                    ></info>         
                    <hr>
                    <div class="row">
                        <div class="col-md-1">
                            <button type="button" class="btn btn-secondary pull-left" id="curation-proceed" @click="router.go(-1)">Cancel</button>
                        </div>
                        <div class="col-md-11 text-end">
                            <b-button variant="primary" id="create-and-continue-btn" @click="createCuration()">Create curation</b-button>
                        </div>
                    </div>
                </b-form>
            </b-card>
        </div>
    </div>
</template>
<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import Info from './Forms/Info.vue'

const emit = defineEmits(['saved', 'created'])
const router = useRouter()
const store = useStore()

const updatedCuration = ref({
    gene_symbol: null,
    gdm_uuid: null
})
const errors = ref({})
const user = computed(() => store.getters.getUser)

function createCuration() {
    return store.dispatch('curations/storeNewItem', updatedCuration.value)
        .then(response => {
            emit('saved')
            emit('created')
            store.commit('messages/addInfo', 'Curation with ' + updatedCuration.value.gene_symbol + ' created.')
            router.push('/curations/' + response.data.data.id + '/edit/#curation-type')
            return response
        })
        .catch(error => {
            errors.value = error.response.data.errors
            return error
        })
}
</script>
