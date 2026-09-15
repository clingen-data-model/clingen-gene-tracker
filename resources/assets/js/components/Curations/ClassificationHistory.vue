<style scoped>
    tr.highlight td {
        font-weight: bold;
    }
</style>
<template>
    <div class="curation-classification-history">
        <!-- <pre>{{curation.classifications}}</pre> -->
        <table class="table table-bordered table-small">
            <tbody>
                <tr>
                    <th>Classification</th>
                    <th>Date</th>
                    <th v-if="canViewSource">Source</th>
                    <th v-if="canViewSource">Source Event Key</th>
                </tr>
                <tr
                    v-for="(classification, idx) in orderedClassifications"
                    :key="classification.pivot.id"
                    :class="{'table-primary highlight': (idx == 0)}"
                >
                    <td>{{classification.name}}</td>
                    <td>{{formatDate(classification.pivot.classification_date, 'YYYY-MM-DD')}}</td>
                    <td v-if="canViewSource">{{classification.pivot.source}}</td>
                    <td v-if="canViewSource">{{classification.pivot.source_event_key}}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import moment from 'moment'
import { formatDate } from '../../filters'

const props = defineProps({
    curation: {
        type: Object,
        required: true
    }
})

const store = useStore()
const user = computed(() => store.getters.getUser)
const canViewSource = computed(() => user.value.canAccessAdministration())

const orderedClassifications = computed(() => {
    if (props.curation.classifications) {
        return props.curation.classifications.concat().sort((a, b) => {
            if (moment(a.pivot.classification_date).isSame(b.pivot.classification_date)) {
                if(a.id == b.id) {
                    return 0
                }
                if (a.id < b.id) {
                    return -1
                }
                return 1
            }
            if (moment(a.pivot.classification_date).isBefore(b.pivot.classification_date)) {
                return 1
            }
            return -1
        })
    }

    return []
})
</script>
