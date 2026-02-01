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
                <td>{{ $formatDate(status.pivot.status_date, 'YYYY-MM-DD') }}</td>
            </tr>
        </table>
    </div>
</template>
<script>
    import dayjs from 'dayjs'

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

                        const dateA = dayjs(a.pivot.status_date);
                        const dateB = dayjs(b.pivot.status_date);

                        if (dateA.isSame(dateB)) {
                            const updatedAtA = dayjs(a.pivot.updated_at);
                            const updatedAtB = dayjs(b.pivot.updated_at);

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
