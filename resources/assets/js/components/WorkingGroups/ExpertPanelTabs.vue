<script>
    import CurationsTable from '../Curations/Table.vue'
    import {startCase} from 'lodash'

    export default {
        props: ['expertPanel'],
        components: {
            CurationsTable
        },
        computed: {
            activeMembers: function () {
                return this.expertPanel?.users.filter(u => u.deactivated_at === null) || []
            }
        },
        methods: {
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
        }
    }
</script>
<template>
    <b-tabs>
        <b-tab title="People">
            <template slot="title">
                People &nbsp;<span class="badge  badge-pill badge-primary">{{activeMembers.length}}</span>
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
                    <tr v-for="user in activeMembers" :key="user.id">
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
                Curations <span class="badge  badge-pill badge-primary">{{expertPanel.curations.length}}</span>
            </template>
            <ul class="list-unstyled mt-2">
                <li class="border-bottom">
                    <curations-table 
                        v-if="expertPanel.curations && expertPanel.curations.length > 0" 
                        :page-length="5"
                        :search-params="{expert_panel_id: expertPanel.id}"
                    ></curations-table>
                    <div v-else class="alert alert-secondary">
                        {{expertPanel.name}} doesn't have any curations yet.
                    </div>
                </li>
            </ul>
        </b-tab>
    </b-tabs>
</template>