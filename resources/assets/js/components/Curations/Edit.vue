<style></style>
<template>
    <div>
        <p>
            <router-link to="/curations">
                &lt; Back to curations
            </router-link>
        </p>
        <b-card id="edit-curation-modal">
            <template #header>
                <div class="d-flex justify-content-between">
                    <h3>{{ title }}</h3>
                    <div class="d-flex space-x-2">
                        <transfer-curation-control 
                            :curation="curation"
                             v-if="transferEnabled"
                        ></transfer-curation-control>
                        <router-link :to="'/curations/'+curation.id">
                            view
                        </router-link>
                    </div>
                </div>
            </template>
            <div v-if="!curation.id" class="alert alert-info">
                Loading...
            </div>
            <div v-else-if="!user.canEditCuration(curation)" class="alert alert-danger">
                Sorry.  You don't have permission to edit this curation.
            </div>
            <div v-if="curations && user.canEditCuration(curation)">
                <b-form id="new-curation-form">
                    <div class="row">
                        <div class="col-md-2 border-end">
                            <nav class="nav flex-column">
                                <router-link 
                                     v-for="(step, idx) in steps"
                                     :key="idx"
                                    :to="route.path+'#'+idx"
                                    class="nav-link" 
                                    :class="{active: (currentStep == idx)}"
                                >
                                    {{ step.title }}
                                </router-link>
                            </nav>
                        </div>
                        <div class="col-md-10">
                            <component 
                                :is="currentStepComponent"
                                :model-value="updatedCuration"  
                                :errors="errors" 
                                @update:model-value="updatedCuration = $event"
                                @auto-save="handleAutoSave"
                                ref="editPage"
                            >
                            </component>                    
                        </div>
                    </div>
                        <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <button type="button" class="btn btn-secondary" @click="router.push('/curations')">Cancel</button>
                        </div>
                        <div v-if="!updatedCuration.is_archived || user.canManageArchive()" class="col-md-8 text-end">
                            <button type="button" class="btn btn-secondary" id="curation" @click="updateCuration()">Save</button>
                            <button v-if="nextStep" type="button" class="btn btn-secondary" @click="updateCuration(exit)">Save &amp; exit</button>
                            <b-button variant="primary" @click="updateCuration(navBack, 'back')" v-show="currentStepIdx > 0">Back</b-button>
                            <b-button variant="primary" @click="updateCuration(navNext, 'next')">
                                {{ (!nextStep) ? 'Save and exit' : 'Next'}}
                            </b-button>
                        </div>
                    </div>
                </b-form>
            </div>
        </b-card>
    </div>
</template>
<script setup>
    import { computed, onMounted, ref, watch } from 'vue'
    import { useRoute, useRouter } from 'vue-router'
    import { useStore } from 'vuex'
    import CurationType from './Forms/CurationType.vue'
    import Phenotypes from './Forms/Phenotypes.vue'
    import Info from './Forms/Info.vue'
    import Mondo from './Forms/Mondo.vue'
    import Classification from './Forms/Classification.vue'
    import Documents from './Forms/Documents.vue'
    import TransferCurationControl from './TransferCurationControl.vue'

    const props = defineProps(['id'])
    const emit = defineEmits(['saved', 'canceled'])
    const route = useRoute()
    const router = useRouter()
    const store = useStore()

    const currentStep = ref('info')
    const steps = {
        info: {
            title: 'Info',
            next: 'curation-type'
        },
        'curation-type': {
            title: 'Curation Type',
            next: 'phenotypes'
        },
        phenotypes: {
            title: 'Phenotypes',
            next: 'mondo'
        },
        mondo: {
            title: 'MonDO',
            next: 'classification',
            back: 'phenotypes'
        },
        documents: {
            title: 'Documents',
            next: null,
            back: 'classification'
        }
    }
    const updatedCuration = ref({
        rationals: []
    })
    const standInCuration = {
        id: 0,
        expert_panel: {},
        rationales: []
    }
    const errors = ref({})
    const editPage = ref(null)

    const user = computed(() => store.getters.getUser)
    const curations = computed(() => store.getters['curations/Items'])
    const transferEnabled = computed(() => store.state.features.transferEnabled)
    const curation = computed(() => {
        if (curations.value.length === 0) {
            return standInCuration
        }

        return store.getters['curations/getItemById'](props.id) || standInCuration
    })
    const title = computed(() => {
        let value = 'Edit Curation: '
        if (curation.value.gene_symbol) {
            value += curation.value.gene_symbol
            if (curation.value.expert_panel) {
                value += ' for ' + curation.value.expert_panel.name
            }
        }
        return value
    })
    const currentStepIdx = computed(() => Object.keys(steps).indexOf(currentStep.value))
    const stepComponents = {
        info: Info,
        'curation-type': CurationType,
        phenotypes: Phenotypes,
        mondo: Mondo,
        classification: Classification,
        documents: Documents
    }
    const currentStepComponent = computed(() => stepComponents[currentStep.value])
    const nextStep = computed(() => {
        const next = steps[currentStep.value].next
        return typeof next === 'function' ? next() : next
    })
    const previousStep = computed(() => {
        const back = steps[currentStep.value].back
        if (back) {
            return typeof back === 'function' ? back() : back
        }
        const stepKeys = Object.keys(steps)
        return currentStepIdx.value > 0 ? stepKeys[currentStepIdx.value - 1] : null
    })

    const fetchCuration = id => store.dispatch('curations/fetchItem', id)
    const storeItemUpdates = payload => store.dispatch('curations/storeItemUpdates', payload)

    function handleAutoSave() {
        if (updatedCuration.value.is_archived && !user.value.canManageArchive()) {
            return
        }
        updateCuration()
    }

    function updateCuration(callback, nav) {
        if (updatedCuration.value.is_archived && !user.value.canManageArchive()) {
            store.commit('messages/addAlert', 'This curation is archived and cannot be edited.')
            return
        }
        updatedCuration.value.nav = nav
        return storeItemUpdates(updatedCuration.value)
            .then(response => {
                store.commit('messages/addInfo', 'Updates saved for ' + updatedCuration.value.gene_symbol + '.')
                emit('saved')
                if (callback) {
                    callback(response)
                }
                errors.value = {}
                return response
            })
            .catch(error => {
                errors.value = error.response.data.errors
                return error
            })
    }

    function navNext() {
        if (nextStep.value) {
            router.push(route.path + '#' + nextStep.value)
            return
        }
        router.push('/')
    }

    function navBack() {
        if (previousStep.value) {
            router.push(route.path + '#' + previousStep.value)
        }
    }

    function exit() {
        router.push('/')
    }

    function setUpdatedCuration(to, from) {
        if (typeof to === 'undefined') {
            return
        }
        if (typeof from === 'undefined') {
            fetchCuration(curation.value.id)
            updatedCuration.value = JSON.parse(JSON.stringify(curation.value))
            return
        }
        if (to.id != from.id && to.id && to.id != 0) {
            fetchCuration(curation.value.id)
            updatedCuration.value = JSON.parse(JSON.stringify(curation.value))
            return
        }
        updatedCuration.value = JSON.parse(JSON.stringify(curation.value))
    }

    function clearForm() {
        updatedCuration.value = {}
        errors.value = {}
    }

    function cancel() {
        emit('canceled')
        clearForm()
    }

    function proceed() {
        currentStep.value = 'disease-entity-fields'
    }

    function setCurrentStep() {
        if (route.hash.substring(1)) {
            currentStep.value = route.hash.substring(1)
        }
    }

    watch(() => route.fullPath, setCurrentStep)
    watch(curation, (to, from) => {
        if (typeof to !== 'undefined') {
            setUpdatedCuration(to, from)
        }
    })

    onMounted(() => {
        fetchCuration(props.id)
        updatedCuration.value = {}
        if (curation.value) {
            setUpdatedCuration(curation.value, {})
        }
        setCurrentStep()
    })

    defineExpose({
        cancel,
        clearForm,
        exit,
        handleAutoSave,
        navBack,
        navNext,
        proceed,
        setCurrentStep,
        setUpdatedCuration,
        updateCuration
    })
</script>
