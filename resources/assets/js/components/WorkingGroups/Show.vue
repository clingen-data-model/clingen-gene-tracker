<script>
    import {mapGetters, mapActions} from 'vuex'
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
        <div class="Working group detail card">
            <div class="card-header">
                <h3>{{group.name}}</h3>
            </div>
            <div class="card-body">
                <h4>Expert Panels</h4>
                <b-tabs pills card vertical v-show="hasPanels" nav-wrapper-class="w-25">
                    <b-tab v-for="panel in group.expert_panels" :key="panel.id" :title="panel.name">
                        <ExpertPanelTabs :expert-panel="panel" />
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
