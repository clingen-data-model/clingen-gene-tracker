<style></style>
<template>
    <div class="new-curation-container">
        <div v-if="!user.hasPermission('create curations')" class="alert alert-danger">
            Sorry.  You don't have permission to create curations.
        </div>
        <div v-else>
            <p>
                <router-link to="/curations">
                        &lt; Back to curations
                </router-link>
            </p>        
            <b-card>
                <template slot="header">
                    <h3>Add a curation to curate</h3>
                </template>
                <b-form id="new-curation-form">
                    <info
                        :value="updatedCuration" 
                        :errors="errors"
                        @input="updatedCuration = $event"
                    ></info>         
                    <hr>
                    <div class="row">
                        <div class="col-md-1">
                            <button type="button" class="btn btn-secondary pull-left" id="curation-proceed" @click="$router.go(-1)">Cancel</button>
                        </div>
                        <div class="col-md-11 text-right">
                            <b-button variant="primary" id="create-and-continue-btn" @click="createCuration()">Create curation</b-button>
                        </div>
                    </div>
                </b-form>
            </b-card>
        </div>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'
    import Info from './Forms/Info'
    export default {
        components: {
            Info
        },
        data: function () {
            return {
                user: user,
                updatedCuration: {
                    gene_symbol: null
                },
                errors: {},
            }
        },
        computed: {
            selectedPanel: function () {
                return this.panels.find(
                    obj => { 
                        return obj.id == this.newPanelId 
                    })
            },
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addAlert'
            ]),
            ...mapActions('curations', {
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
        mounted: function() {
        }
        //component definition
    }
</script>