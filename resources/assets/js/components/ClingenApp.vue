<style></style>
<template>
    <div class="clingen-app-container container">
        <b-card>
            <template slot="header">
                <button 
                    id="new-gene-btn" 
                    class="btn btn-secondary float-right btn-sm" 
                    @click="toggleNewGeneForm()"
                >{{ newGeneButtonLabel }}</button>
                 <h3>Genes in curation</h3>
            </template>
            <b-list-group>
                <b-list-group-item 
                    v-for="gene in genes"
                    v-bind:key="gene.symbol"
                >
                    {{ gene.symbol }} - {{ gene.expert_panel.name }}
                </b-list-group-item>
            </b-list-group>
        </b-card>

        <b-modal 
            v-model="newGeneFormVisible" 
            title="Add a gene to curate"
            hide-footer
            ok-title="Save"
        >
            <new-gene-form 
                @new-gene-saved="handleNewGene($event)"
                @new-gene-canceled="toggleNewGeneForm()"
            >
            </new-gene-form>
        </b-modal>
    </div>
</template>
<script>
    export default {
        components: {
            'new-gene-form': require('./NewGeneForm.vue'),
        },
        data: function() {
            return {
                'newGeneFormVisible': false,
                genes: []
            }
        },
        computed: {
            newGeneButtonLabel: function () {
                return (!this.newGeneFormVisible) ? 'Add new Gene' : 'Close gene form';
            }
        },
        methods: {
            handleNewGene: function (gene) {
                this.genes.push(gene);
                this.toggleNewGeneForm();
            },
            toggleNewGeneForm: function () {
                console.log('toggleNewGeneForm');
                this.newGeneFormVisible = !this.newGeneFormVisible;
            }
        },
        mounted: function () {
        }
    }
</script>