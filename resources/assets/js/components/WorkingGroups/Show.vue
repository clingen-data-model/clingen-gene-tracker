<style></style>
<template>
    <div>
        <div>
            <router-link to="/working-groups">
                    &lt; Back to working groups
            </router-link>
        
        </div>
        <div class="Working group detail card">
            <div class="card-header">
                <h3>{{group.name}}</h3>
            </div>
            <div class="card-body">
                <div class="row mt-1">
                    <strong class="col-md-2">Expert panels:</strong>
                    <div class="col-md">
                        <ul class="list-unstyled" v-for="panel in group.expert_panels" v-show="hasPanels">
                            <li class="border-bottom">
                                <h4 class="border-bottom pb-1 mb-2">{{panel.name}}</h4>
                                <div class="row">
                                    <div class="col-md-2">Topics</div>
                                    <div class="col-md">
                                        <topics-table 
                                            v-if="panel.topics && panel.topics.length > 0" 
                                            :topics="panel.topics"
                                            :page-length="5"
                                        ></topics-table>
                                        <div v-else class="alert alert-secondary">
                                            {{panel.name}} doesn't have any topics yet.
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div class="alert alert-secondary" v-show="!hasPanels">
                            This working group does not have any expert panels
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import {mapGetters, mapActions} from 'vuex'
    import TopicsTable from '../Topics/Table'

    export default {
        props: ['id'],
        components: {
            TopicsTable
        },
        beforeRouteUpdate (to, from, next) {
            this.fetchGroup(this.id);
            next()
        },
        data() {
            return {
                hasPanels: false
            };
        },
        computed: {
            ...mapGetters('workingGroups', {
                groups: 'Items',
                getGroup: 'getItemById'
            }),
            group: function () {
                if (this.groups.length == 0) {
                    return {};
                }
                const group = this.getGroup(this.id)
                this.hasPanels = group && group.expert_panels && group.expert_panels.length > 0
                return group
            },
        },
        methods: {
            ...mapActions('workingGroups', {
                fetchGroup: 'fetchItem'
            }),
        },
        mounted() {
            this.fetchGroup(this.id);
        }
    }
</script>