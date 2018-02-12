<style></style>
<template>
    <div class="new-gene-form-container">
        <b-form id="new-gene-form">
            <b-form-group id="new-gene-symbol-group"
                label="Gene Symbol"
                label-for="gene-symbol-input"
            >
                <b-form-input id="gene-symbol-input"
                type="text"
                v-model="newGeneSymbol"
                required
                placeholder="ATK-1">                    
                </b-form-input>
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
    import { mapGetters, mapActions } from 'vuex'

    export default {
        data: function () {
            return {
                newGeneSymbol: null,
                newPanelId: null,
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
            }
        },
        methods: {
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            saveGene: function ()
            {
                this.$emit('new-gene-saved', {symbol: this.newGeneSymbol, expert_panel: this.selectedPanel});
                this.clearForm();
            },
            cancelSave: function ()
            {
                this.$emit('new-gene-canceled');
                this.clearForm();
            },
            clearForm: function () {
                this.newGeneSymbol = null
                this.newPanelId = null
            }
        },
        mounted: function() {
            this.getAllPanels();
        }
    }
</script>