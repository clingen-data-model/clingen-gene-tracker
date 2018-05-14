<style scoped>
    tr.highlight td {
        font-weight: bold;
    }
</style>
<template>
    <div class="topic-status-history">
        <table class="table table-bordered table-small">
            <tr>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <tr v-for="(status, idx) in orderedStatuses" :class="{'table-primary highlight': (idx == 0)}">
                <td>{{status.name}}</td>
                <td>{{status.pivot.created_at | formatDate('Y-M-D H:m') }}</td>
            </tr>
        </table>
    </div>
</template>
<script>
    import moment from 'moment'

    export default {
        props: {
            topic: {
                type: Object,
                required: true
            },
        },
        computed: {
            orderedStatuses: function () {
                if (this.topic.topic_statuses) {
                    return this.topic.topic_statuses.concat().sort((a, b) => {
                        if (a == b) {
                            return 0;
                        }
                        if (moment(a.pivot.created_at).isBefore(b)) {
                            return -1;
                        }
                        return 1;
                    })
                }
                return [];
            }
        }
    }
</script>