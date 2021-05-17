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
                :key="(indexAttribute) ? item[keyAttribute] : idx"
                :class="{'table-primary highlight': (idx == 0)}"
            >
                <td>{{item.name}}</td>
                <td>{{item.pivot[dateField] | formatDate('YYYY-MM-DD') }}</td>
            </tr>
        </table>
    </div>
</template>
<script>
    import moment from 'moment'
    import filters from '../../filters'
    
    export default {
        props: {
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
        },
        computed: {
            orderedItems: function () {
                if (this.items) {
                    return this.items.concat().sort((a, b) => {
                        if (moment(a.pivot[this.dateField]).isSame(b.pivot[this.dateField])) {
                            if(a.id == b.id) {
                                return 0
                            }
                            if (a.id < b.id) {
                                return 1
                            }
                            return -1;
                        }
                        if (moment(a.pivot[this.dateField]).isBefore(b.pivot[this.dateField])) {
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