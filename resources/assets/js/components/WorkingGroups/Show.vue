<style></style>
<template>
    <div>
        <div>
            <router-link to="/working-groups">
                Working groups 
            </router-link>
            &gt;
            <router-link :to="`/working-groups/${group.id}`">
                {{group.name}}
            </router-link>
        </div>
        <div class="Working group detail card">
            <div class="card-header">
                <h3>{{group.name}}</h3>
            </div>
            <div class="card-body">
                <h4>Expert Panels</h4>
                <b-tabs pills card vertical v-show="hasPanels" nav-wrapper-class="w-25">
                    <b-tab v-for="panel in group.expert_panels" :key="panel.id" :title="panel.name">
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
                                        <tr v-for="user in panel.users" :key="user.id">
                                            <td>{{user.name}}</td>
                                            <td>{{user.email}}</td>
                                            <td>
                                                {{getUserRoles(user).join(', ')}}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </b-tab> 
                            <b-tab>
                                <template slot="title">
                                    Curations <span class="badge  badge-pill badge-primary">{{panel.curations.length}}</span>
                                </template>
                                <ul class="list-unstyled mt-2">
                                    <li class="border-bottom">
                                        <curations-table 
                                            v-if="panel.curations && panel.curations.length > 0" 
                                            :page-length="5"
                                            :search-params="{expert_panel_id: panel.id}"
                                        ></curations-table>
                                        <div v-else class="alert alert-secondary">
                                            {{panel.name}} doesn't have any curations yet.
                                        </div>
                                    </li>
                                </ul>
                            </b-tab>
                        </b-tabs>
                    </b-tab>

                </b-tabs>
                <div class="alert alert-secondary" v-show="!hasPanels && !loading">
                    This working group does not have any expert panels
                </div>
                <div class="alert alert-secondary" v-show="loading">
                    Loading &hellip;
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import {mapGetters, mapActions} from 'vuex'
    import CurationsTable from '../Curations/Table'
    import {startCase} from 'lodash'

    export default {
        props: ['id'],
        components: {
            CurationsTable
        },
        beforeRouteUpdate (to, from, next) {
            this.fetchGroup(this.id);
            next()
        },
        data() {
            return {
                hasPanels: false,
                loading: false
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
            getUserRoles(user) {
                let roles = user.roles.map(role=>startCase(role.name));
                if (user.pivot.is_coordinator) {
                    roles.push('Coordinator');
                }
                if (user.pivot.is_curator) {
                    roles.push('Curator');
                }
                // console.log(roles);
                return roles;
            }
        },
        mounted() {
            this.loading = true;
            this.fetchGroup(this.id)
                .then(response => {
                    this.loading = false;
                })
                .catch(error => {
                    this.loading = false;
                });
        }
    }
</script>