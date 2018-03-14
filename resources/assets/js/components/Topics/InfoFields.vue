<style></style>
<template>
    <div id="topic-info-fields">
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC topic Symbol"
            label-for="gene-symbol-input"
        >
            <b-form-input id="gene-symbol-input"
                type="text"
                v-model="updatedTopic.gene_symbol"
                required
                placeholder="ATK-1"
                :state="geneSymbolError">                    
            </b-form-input>
            <b-form-invalid-feedback id="geneSymbolError">
                {{errors.gene_symbol}}
            </b-form-invalid-feedback>
        </b-form-group>
    
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedTopic.expert_panel_id">
                <option :value="null">Select...</option>
                <option v-for="panel in panels" :value="panel.id">{{panel.name}}</option>
            </b-form-select>
        </b-form-group>
    
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedTopic.curator_id">
                <option :value="null">Select...</option>
                <option v-for="curator in curators" :value="curator.id">{{curator.name}}</option>
            </b-form-select>
        </b-form-group>
    
        <b-form-group horizontal label="Notes" label-for="notes-field">
            <textarea id="notes-field" class="form-control" placeholder="optional notes" v-model="updatedTopic.notes"></textarea>
        </b-form-group>
    </div>
</template>
<script>
    import { mapGetters, mapActions, mapMutations } from 'vuex'

    export default {
        props: ['value', 'errors'],
        data: function () {
            return {
                updatedTopic: {}
            }
        },
        computed: {
            ...mapGetters('panels', {
                panels: 'Items',
            }),
            ...mapGetters('users', {
                curators: 'getCurators'
            }),
            geneSymbolError: function () {
                return (this.errors && this.errors.gene_symbol && this.errors.gene_symbol.length > 0) ? false : null;
            },
        },
        watch: {
            updatedTopic: function () {
                this.$emit('input', this.updatedTopic);
            },
            value: function () {
                if (this.value != this.updatedTopic) {
                    this.syncValue();
                }
            }
        },
        methods: {
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'
            }),
            ...mapActions('users', {
                getAllUsers: 'getAllItems'
            }),
            syncValue: function () {
                if (this.value) {
                    this.updatedTopic = JSON.parse(JSON.stringify(this.value));
                }
            }
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
            this.syncValue();
        }
    }
</script>