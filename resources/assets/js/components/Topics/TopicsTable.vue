<style></style>
<template>
    <div class="topics-table-container">
        <b-table striped hover :items="tableItems" :fields="fields">            
            <template slot="actions" slot-scope="data">
            <router-link
                id="new-topic-btn" 
                class="btn btn-secondary float-right btn-sm" 
                :to="'/topics/'+data.item.id+'/edit'"
            >
                Edit
            </router-link>
            </template>
        </b-table>
    </div>
</template>
<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {
        data: function () {
            return {
                fields: {
                    gene_symbol: {
                        label: 'Gene Symbol',
                        sortable: true
                    },
                    expert_panel: {
                        label: 'Expert Panel',
                        sortable: true,
                    },
                    curator: {
                        label: 'Curator',
                        sortable: true,
                    },
                    actions: {
                        label: 'Actions',
                        sortable: false,
                    }
                },
            }
        },
        computed: {
            ...mapGetters('topics', {
                topics: 'Items'
            }),
            tableItems: function () {
                return this.topics.map(function (item){
                    return {
                        id: item.id,
                        gene_symbol: item.gene_symbol,
                        curator: (item.curator) ? item.curator.name : null,
                        expert_panel: (item.expert_panel) ? item.expert_panel.name : null,
                    }
                });
            }
        },
        methods: {
            ...mapActions('topics', {
                getAllTopics: 'getAllItems'
            })
        },
        mounted: function () {
            if (this.topics.length == 0) {
                this.getAllTopics();
            }
        }
    }
</script>