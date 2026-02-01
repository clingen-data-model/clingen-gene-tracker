<style scoped>
    tr.highlight td {
        font-weight: bold;
    }
</style>
<template>
    <div class="curation-classification-history">
        <table class="table table-bordered table-small">
            <tr>
                <th>Classification</th>
                <th>Date</th>
            </tr>
            <tr
                v-for="(classification, idx) in orderedClassifications"
                :key="classification.pivot.id"
                :class="{'table-primary highlight': (idx == 0)}"
            >
                <td>{{classification.name}}</td>
                <td>{{ $formatDate(classification.pivot.classification_date, 'YYYY-MM-DD') }}</td>
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
            orderedClassifications: function () {
                if (this.curation.classifications) {
                    return this.curation.classifications.concat().sort((a, b) => {
                        if (dayjs(a.pivot.classification_date).isSame(b.pivot.classification_date)) {
                            if(a.id == b.id) {
                                return 0
                            }
                            if (a.id < b.id) {
                                return -1
                            }
                            return 1;
                        }
                        if (dayjs(a.pivot.classification_date).isBefore(b.pivot.classification_date)) {
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
