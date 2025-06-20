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
                :key="status.pivot.id" 
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
            /** THE STATUSES ORDERED BY THE NEWEST FIRST, ORDERED BASED ON STATUS_DATE DESC, UPDATED_AT DESC */
            orderedStatuses: function () {
                if (this.curation.curation_statuses) {
                    return this.curation.curation_statuses.concat().sort((a, b) => {

                        const dateA = moment(a.pivot.status_date);
                        const dateB = moment(b.pivot.status_date);

                        if (dateA.isSame(dateB)) {
                            const updatedAtA = moment(a.pivot.updated_at);
                            const updatedAtB = moment(b.pivot.updated_at);

                            if (updatedAtA.isSame(updatedAtB)) {
                                return 0;
                            }
                            return updatedAtA.isBefore(updatedAtB) ? 1 : -1;
                        }

                        return dateA.isBefore(dateB) ? 1 : -1;
                    })
                }
                return [];
            }
        }
    }
</script>