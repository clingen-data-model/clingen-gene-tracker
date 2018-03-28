<style></style>
<template>
    <div id="topic-info-fields">
        <b-form-group horizontal id="new-gene-symbol-group"
            label="HGNC Gene Symbol"
            label-for="gene-symbol-input"
        >
            <b-form-input id="gene-symbol-input"
                type="text"
                :value="geneSymbol"
                @input="updateTopicGeneSymbol($event)"
                required
                placeholder="ATK-1"
                :state="geneSymbolError"> 
            </b-form-input>
            <b-form-invalid-feedback id="geneSymbolError">
                 <div v-for="msg in errors.gene_symbol">{{msg}}</div>
            </b-form-invalid-feedback>
        </b-form-group>
        <transition name="fade">
            <div class="row justify-content-end">
                <div class="col-md-9 alert alert-warning" v-show="matchedCount > 0">
                    <div class="clearfix">
                        There are already <strong>{{matchedCount}}</strong> topics in curation or pre-curation with this gene symbol.
                        <button class="btn btn-sm btn-warning float-right" v-b-toggle.matching-topics-details>Details</button>
                    </div>
                    <b-collapse id="matching-topics-details" class="mt-2">
                        <div class="card mb-3 ml-3" v-for="topic in matchingTopics">
                            <div class="card-body">
                                <strong>{{topic.gene_symbol}} for {{topic.expert_panel.name}}</strong>
                                <div>
                                    Phenotypes: 
                                    <ul class="list-inline">
                                        <li class="list-inline-item" v-for="phenotype in topic.phenotypes">{{phenotype.mim_number}},</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </b-collapse>
                </div>
            </div>
        </transition>
        <b-form-group horizontal id="expert-panel-select-group" label="Gene Curation Expert Panel" label-for="expert-panel-select">
            <b-form-select id="expert-panel-select" v-model="updatedTopic.expert_panel_id" :state="expertPanelIdError">
                <option :value="null">Select...</option>
                <option v-for="panel in panels" :value="panel.id">{{panel.name}}</option>
            </b-form-select>
            <b-form-invalid-feedback id="expertPanelIdError">
                <div v-for="msg in errors.expert_panel_id">{{msg}}</div>
            </b-form-invalid-feedback>
        </b-form-group>
    
        <b-form-group horizontal id="expert-panel-select-group" label="Curator" label-for="expert-panel-select">
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
    import _ from 'lodash'

    export default {
        name: 'test',
        props: ['value', 'errors'],
        data: function () {
            return {
                geneSymbol: null,
                updatedTopic: {
                    gene_symbol: null
                },
                matchingTopics: [],
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
            expertPanelIdError: function () {
                return (this.errors && this.errors.expert_panel_id && this.errors.expert_panel_id.length > 0) ? false : null;
            },
            matchedCount: function () {
                console.log(this.matchingTopics);
                const keys = Object.keys(this.matchingTopics)
                return keys.length
            }
        },
        watch: {
            updatedTopic: function (to, from) {
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
            },
            updateTopicGeneSymbol(symbol) {
                this.geneSymbol = symbol;
                this.updatedTopic.gene_symbol = this.geneSymbol
                this.checkTopics();
            },
            checkTopics: _.debounce(function() {
                console.log(this.geneSymbol);
                window.axios.get('/api/topics?with=phenotypes&gene_symbol='+this.geneSymbol)
                    .then((response) => {
                        console.log(this.name);
                        console.log(response.data.data);
                        this.matchingTopics = response.data.data
                    })
            }, 500),
        },
        mounted: function () {
            this.getAllPanels();
            this.getAllUsers();
            this.syncValue();
            this.dumpCheckTopics();
        }
    }
</script>