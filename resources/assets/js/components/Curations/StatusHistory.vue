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
                <td>{{formatDate(status.pivot.status_date, 'YYYY-MM-DD')}}</td>
            </tr>
        </table>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import moment from 'moment'
import { formatDate } from '../../filters'

const props = defineProps({
    curation: {
        type: Object,
        required: true
    }
})

/** THE STATUSES ORDERED BY THE NEWEST FIRST, ORDERED BASED ON STATUS_DATE DESC, UPDATED_AT DESC */
const orderedStatuses = computed(() => {
    if (props.curation.curation_statuses) {
        return props.curation.curation_statuses.concat().sort((a, b) => {

            const dateA = moment(a.pivot.status_date)
            const dateB = moment(b.pivot.status_date)

            if (dateA.isSame(dateB)) {
                const updatedAtA = moment(a.pivot.updated_at)
                const updatedAtB = moment(b.pivot.updated_at)

                if (updatedAtA.isSame(updatedAtB)) {
                    return 0
                }
                return updatedAtA.isBefore(updatedAtB) ? 1 : -1
            }

            return dateA.isBefore(dateB) ? 1 : -1
        })
    }

    return []
})
</script>
