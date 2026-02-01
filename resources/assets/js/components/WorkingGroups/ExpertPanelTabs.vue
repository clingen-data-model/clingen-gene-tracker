<script>
    import CurationsTable from '../Curations/Table.vue'
    import { startCase } from 'lodash-es'

    export default {
        props: ['expertPanel'],
        components: {
            CurationsTable
        },
        data() {
            return {
                activeTab: 0,
            }
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
                return roles;
            }
        }
    }
</script>
<template>
    <div>
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 0 }" @click="activeTab = 0">
                    People &nbsp;<span class="badge rounded-pill bg-primary">{{activeMembers.length}}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 1 }" @click="activeTab = 1">
                    Curations <span class="badge rounded-pill bg-primary">{{expertPanel.curations.length}}</span>
                </button>
            </li>
        </ul>
        <div v-show="activeTab === 0">
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
        </div>
        <div v-show="activeTab === 1">
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
        </div>
    </div>
</template>
