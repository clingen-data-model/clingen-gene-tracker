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
            <tr 
                v-for="(status, idx) in orderedStatuses" 
                :key="status.id" 
                :class="{'table-primary highlight': (idx == 0)}"
            >
                <td>{{status.name}}</td>
                <td>{{status.pivot.status_date | formatDate('YYYY-MM-DD') }}</td>
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
                        if (moment(a.pivot.status_date).isSame(b.pivot.status_date)) {
                            if(a.id == b.id) {
                                return 0
                            }
                            if (a.id < b.id) {
                                return 1
                            }
                            return -1;
                        }
                        if (moment(a.pivot.status_date).isBefore(b.pivot.status_date)) {
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