<style scoped>
    tr.highlight td {
        font-weight: bold;
    }
</style>
<template>
    <div>
        <table class="table table-bordered table-small">
            <tr>
                <th>{{itemLabel}}</th>
                <th>Date</th>
            </tr>
            <tr 
                v-for="(item, idx) in orderedItems" 
                :key="(indexAttribute) ? item[indexAttribute] : idx"
                :class="{'table-primary highlight': (idx == 0)}"
            >
                <td>{{item.name}}</td>
                <td>{{formatDate(item.pivot[dateField], 'YYYY-MM-DD')}}</td>
            </tr>
        </table>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import moment from 'moment'
import { formatDate } from '../../filters'

const props = defineProps({
    items: {
        type: Array,
        required: true
    },
    itemLabel: {
        type: String,
        required: true
    },
    dateField: {
        type: String,
        required: true,
    },
    indexAttribute: {
        type: String,
        required: false,
        default: null
    }
})

const orderedItems = computed(() => {
    if (props.items) {
        return props.items.concat().sort((a, b) => {
            if (moment(a.pivot[props.dateField]).isSame(b.pivot[props.dateField])) {
                if(a.id == b.id) {
                    return 0
                }
                if (a.id < b.id) {
                    return 1
                }
                return -1
            }
            if (moment(a.pivot[props.dateField]).isBefore(b.pivot[props.dateField])) {
                return 1
            }
            return -1
        })
    }

    return []
})
</script>
