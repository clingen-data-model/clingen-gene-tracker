<style></style>

<template>
    <div class="component-container">
        <div class="card w-50">
            <div class="card-header">
                Export Curation Data
            </div>
            <div class="card-body">
                <form action="/curations/export" method="GET">
                    <div class="mb-3">
                        <label for="expert_panel_id">Expert Panel:</label>
                        <select name="expert_panel_id" id="expert_panel_id" class="form-control">
                            <option value="">All</option>
                            <option v-for="panel in panels" :key="panel.id" :value="panel.id">{{panel.name}}</option>
                        </select>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-sm-6">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary" type="submit">Download Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapState, mapActions } from 'pinia'
    import { usePanelsStore } from '../../stores/panels'

    export default {
        components: {},
        data() {
            return {}
        },
        computed: {
            ...mapState(usePanelsStore, {
                panels: 'Items',
            }),
        },
        methods: {
            ...mapActions(usePanelsStore, {
                getAllPanels: 'getAllItems'
            })
        },
        mounted() {
            this.getAllPanels();
        }

}
</script>
