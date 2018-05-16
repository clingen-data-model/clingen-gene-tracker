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
                <h4>Expert Panels</h4>
                <b-tabs pills card vertical v-show="hasPanels">
                    <b-tab v-for="(panel, idx) in group.expert_panels" :key="panel.id" :title="panel.name">
                        <b-tabs>
                           <b-tab title="People">
                                <template slot="title">
                                    People &nbsp;<span class="badge  badge-pill badge-primary">{{panel.users.length}}</span>
                                </template>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Roles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="user in panel.users">
                                            <td>{{user.name}}</td>
                                            <td>{{user.email}}</td>
                                            <td>{{user.roles.map(role=>role.name).join(', ')}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </b-tab> 
                            <b-tab>
                                <template slot="title">
                                    Curation Topics <span class="badge  badge-pill badge-primary">{{panel.topics.length}}</span>
                                </template>
                                <ul class="list-unstyled mt-2">
                                    <li class="border-bottom">
                                        <topics-table 
                                            v-if="panel.topics && panel.topics.length > 0" 
                                            :topics="panel.topics"
                                            :page-length="5"
                                        ></topics-table>
                                        <div v-else class="alert alert-secondary">
                                            {{panel.name}} doesn't have any topics yet.
                                        </div>
                                    </li>
                                </ul>
                            </b-tab>
                        </b-tabs>
                    </b-tab>

                </b-tabs>
                <div class="alert alert-secondary" v-show="!hasPanels">
                    This working group does not have any expert panels
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