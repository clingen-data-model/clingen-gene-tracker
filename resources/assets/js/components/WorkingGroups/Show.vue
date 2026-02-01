<script>
    import { mapState, mapActions } from 'pinia'
    import { useWorkingGroupsStore } from '../../stores/workingGroups'
    import ExpertPanelTabs from './ExpertPanelTabs.vue'

    export default {
        props: ['id'],
        components: {
            ExpertPanelTabs
        },
        beforeRouteUpdate (to, from, next) {
            this.fetchGroup(this.id);
            next()
        },
        data() {
            return {
                hasPanels: false,
                loading: false,
                activePanel: 0,
            };
        },
        computed: {
            ...mapState(useWorkingGroupsStore, {
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
            ...mapActions(useWorkingGroupsStore, {
                fetchGroup: 'fetchItem'
            }),
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
        <div class="card">
            <div class="card-header">
                <h3>{{group.name}}</h3>
            </div>
            <div class="card-body">
                <h4>Expert Panels</h4>
                <div class="d-flex" v-show="hasPanels">
                    <div class="nav flex-column nav-pills me-3" style="min-width: 25%;" role="tablist" aria-orientation="vertical">
                        <button
                            v-for="(panel, index) in group.expert_panels"
                            :key="panel.id"
                            class="nav-link"
                            :class="{ active: activePanel === index }"
                            @click="activePanel = index"
                        >
                            {{ panel.name }}
                        </button>
                    </div>
                    <div class="flex-grow-1">
                        <div
                            v-for="(panel, index) in group.expert_panels"
                            :key="panel.id"
                            v-show="activePanel === index"
                        >
                            <ExpertPanelTabs :expert-panel="panel" />
                        </div>
                    </div>
                </div>
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
