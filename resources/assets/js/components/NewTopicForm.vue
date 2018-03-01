<style></style>
<template>
    <div class="new-gene-form-container">
        <b-form id="new-gene-form">
            <b-form-group id="new-gene-symbol-group"
                label="HGNC Gene Symbol"
                label-for="gene-symbol-input"
            >
                <b-form-input id="gene-symbol-input"
                type="text"
                v-model="newGeneSymbol"
                required
                placeholder="ATK-1"
                :state="geneSymbolError">                    
                </b-form-input>
                <b-form-invalid-feedback id="geneSymbolError">
                    {{errors.gene_symbol}}
                </b-form-invalid-feedback>
            </b-form-group>
            <b-form-group id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
                <b-form-select id="expert-panel-select" v-model="newPanelId">
                    <option :value="null">Select...</option>
                    <option v-for="panel in panels" :value="panel.id">{{panel.name}}</option>
                </b-form-select>
            </b-form-group>
            <div class="text-right">
                <hr>
                <b-button variant="default" id="new-gene-form-cancel" @click="cancelSave()">Cancel</b-button>
                <b-button variant="primary" id="new-gene-form-save" @click="saveGene()">Save</b-button>
            </div>
        </b-form>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'

    export default {
        data: function () {
            return {
                newGeneSymbol: null,
                newPanelId: null,
                errors: {}
            }
        },
        computed: {
            ...mapGetters('panels', {
                panels: 'Items'
            }),
            selectedPanel: function () {
                return this.panels.find(
                    obj => { 
                        return obj.id == this.newPanelId 
                    })
            },
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            }
        },
        methods: {
            ...mapMutations('messages', [
                'addInfo',
                'addAlert'
            ]),
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('topics', {
                storeTopic: 'storeNewItem'
            }),
            saveGene: function ()
            {
                this.storeTopic({'gene_symbol': this.newGeneSymbol, 'expert_panel_id': this.newPanelId})
                    .then(function (response) {
                        this.addInfo('Topic for gene '+this.newGeneSymbol+' saved for '+this.selectedPanel.name+'.')
                        this.$emit('new-gene-saved', {symbol: this.newGeneSymbol, expert_panel: this.selectedPanel});
                        this.clearForm();
                    }.bind(this))
                    .catch(function (error) {
                        this.errors = error.response.data.errors;
                    }.bind(this));
            },
            cancelSave: function ()
            {
                this.$emit('new-gene-canceled');
                this.clearForm();
            },
            clearForm: function () {
                this.newGeneSymbol = null
                this.newPanelId = null
                this.errors = {}
            }
        },
        mounted: function() {
            this.getAllPanels();
        }
    }
</script>