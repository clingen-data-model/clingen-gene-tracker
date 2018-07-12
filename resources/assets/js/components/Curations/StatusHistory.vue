<style scoped>
    tr.highlight td {
        font-weight: bold;
    }
</style>
<template>
    <div class="curation-status-history">
        <table class="table table-bordered table-small">
            <tr>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <tr v-for="(status, idx) in orderedStatuses" :key="status.id" :class="{'table-primary highlight': (status.id == curation.current_status.id)}">
                <td>{{status.name}}</td>
                <td>{{status.pivot.created_at | formatDate('Y-MM-DD') }}</td>
            </tr>
        </table>
    </div>
</template>
<script>
    import moment from 'moment'
    import filters from '../../filters'
    
    export default {
        props: {
            curation: {
                type: Object,
                required: true
            },
        },
        computed: {
            orderedStatuses: function () {
                if (this.curation.curation_statuses) {
                    return this.curation.curation_statuses.concat().sort((a, b) => {
                        if (moment(a.pivot.created_at).isSame(b.pivot.created_at)) {
                            if(a.id == b.id) {
                                return 0
                            }
                            if (a.id < b.id) {
                                return 1
                            }
                            return -1;
                        }
                        if (moment(a.pivot.created_at).isBefore(b.pivot.created_at)) {
                            return 1;
                        }
                        return -1;
                    })
                }
                return [];
            }
        }
    }
</script>