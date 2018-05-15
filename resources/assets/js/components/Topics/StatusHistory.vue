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
                <td>{{status.pivot.created_at | formatDate('Y-M-D') }}</td>
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