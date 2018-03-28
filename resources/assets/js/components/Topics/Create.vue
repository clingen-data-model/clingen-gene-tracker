<style></style>
<template>
    <div class="new-topic-container">
        <p>
            <router-link to="/topics">
                    &lt; Back to topics
            </router-link>
        </p>        
        <b-card>
            <template slot="header">
                <h3>Add a topic to curate</h3>
            </template>
            <b-form id="new-topic-form">
                <info
                    :value="updatedTopic" 
                    :errors="errors"
                    @input="updatedTopic = $event"
                ></info>         
                <hr>
                <div class="row">
                    <div class="col-md-1">
                        <button type="button" class="btn btn-secondary pull-left" id="topic-proceed" @click="$router.go(-1)">Cancel</button>
                    </div>
                    <div class="col-md-11 text-right">
                        <b-button variant="primary" id="create-and-continue-btn" @click="createTopic()">Create topic</b-button>
                    </div>
                </div>
            </b-form>
        </b-card>
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
                updatedTopic: {
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
            ...mapActions('topics', {
                fetchTopic: 'fetchItem',
                storeNewItem: 'storeNewItem',
                storeItemUpdates: 'storeItemUpdates'
            }),
            createTopic () {
                return this.storeNewItem(this.updatedTopic)
                    .then( (response) => {
                        this.$emit('saved');
                        this.$emit('created');
                        this.addInfo('Topic with '+this.updatedTopic.gene_symbol+' created.')
                        this.$router.push('/topics/'+response.data.data.id+'/edit/#phenotypes');
                        return response;
                    })
                    .catch( (error) => {
                        console.log(error);
                        this.errors = error.response.data.errors;
                        return error;
                    })
            },
            clearForm: function () {
                this.updatedTopic = {};
                this.errors = {}
            }
        },
        mounted: function() {
        }
        //component definition
    }
</script>