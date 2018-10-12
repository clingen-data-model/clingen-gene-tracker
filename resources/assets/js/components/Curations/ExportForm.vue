<style></style>

<template>
    <div class="component-container">
        <div class="card w-50">
            <div class="card-header">
                Export Curation Data
            </div>
            <div class="card-body">
                <form action="/curations/export" method="GET">
                    <div class="form-group">
                        <label for="expert_panel_id">Expert Panel:</label>
                        <select name="expert_panel_id" id="expert_panel_id" class="form-control">
                            <option value="">All</option>
                            <option v-for="panel in panels" :key="panel.id" :value="panel.id">{{panel.name}}</option>
                        </select>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-6">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Download Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {
        components: {},
        data() {
            return {}
        },
        computed: {
            ...mapGetters('panels', {
                panels: 'Items',
            }),
        },
        methods: {
            ...mapActions('panels', {
                getAllPanels: 'getAllItems'                
            })
        },
        mounted() {
            this.getAllPanels();
        }
    
}
</script>