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
            <div class="card">
                <div class="card-header">
                    <h3>Add a curation to curate</h3>
                </div>
                <div class="card-body">
                    <form id="new-curation-form" @submit.prevent>
                        <info
                            :modelValue="updatedCuration"
                            :errors="errors"
                            @update:modelValue="updatedCuration = $event"
                        ></info>
                        <hr>
                        <div class="row">
                            <div class="col-md-1">
                                <button type="button" class="btn btn-secondary pull-left" id="curation-proceed" @click="$router.go(-1)">Cancel</button>
                            </div>
                            <div class="col-md-11 text-end">
                                <button class="btn btn-primary" id="create-and-continue-btn" @click="createCuration()">Create curation</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { mapState, mapActions } from 'pinia'
    import { useAppStore } from '../../stores/app'
    import { useMessagesStore } from '../../stores/messages'
    import { useCurationsStore } from '../../stores/curations'
    import Info from './Forms/Info.vue'

    export default {
        components: {
            Info
        },
        data: function () {
            return {
                updatedCuration: {
                    gene_symbol: null,
                    gdm_uuid: null
                },
                errors: {},
            }
        },
        computed: {
            ...mapState(useAppStore, {user: 'getUser'}),
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
        },
        methods: {
            ...mapActions(useMessagesStore, ['addInfo']),
            ...mapActions(useCurationsStore, {
                fetchCuration: 'fetchItem',
                storeNewItem: 'storeNewItem',
                storeItemUpdates: 'storeItemUpdates'
            }),
            createCuration () {
                return this.storeNewItem(this.updatedCuration)
                    .then( (response) => {
                        this.$emit('saved');
                        this.$emit('created');
                        this.addInfo('Curation with '+this.updatedCuration.gene_symbol+' created.')
                        this.$router.push('/curations/'+response.data.data.id+'/edit/#curation-type');
                        return response;
                    })
                    .catch( (error) => {
                        this.errors = error.response.data.errors;
                        return error;
                    })
            },
            clearForm: function () {
                this.updatedCuration = {};
                this.errors = {}
            }
        },
    }
</script>
