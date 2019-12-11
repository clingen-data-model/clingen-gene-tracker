<style></style>

<template>
    <div>
        <b-button 
            variant="info"
            size="sm" 
            class="form-control mb-2"
            @click="modalVisible = true"
        >Add or update status</b-button>

        <curation-status-history :curation="value"></curation-status-history>

        <b-modal 
            v-model="modalVisible"
            @hide="submitAll"
        >
            <div slot="modal-header">
                <h3>Update Curation Status</h3>
            </div>    
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <b-form-select id="expert-panel-select" v-model="newStatusId">
                                <option :value="null">Select...</option>
                                <option v-for="status in statusOptions"
                                    :key="status.id"
                                    :value="status.id"
                                >
                                    {{status.name}}
                                </option>
                            </b-form-select>
                            <div class="text-danger" v-if="errors.curation_status_id">
                                <div v-for="message in errors.curation_status_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="form-inline">
                            <datepicker 
                                v-model="newStatusDate"
                                input-class="form-control mr-2"
                                format='yyyy-MM-dd'
                                calendar-class="small-calendar"
                                placeholder="Select a date"
                                :highlighted="highlighted"
                            ></datepicker>
                            <b-button 
                                variant="primary"
                                @click="addStatus"
                            >
                                <strong>+</strong>
                            </b-button>
                        </td>
                    </tr>
                    <tr v-for="status in curationCopy.curation_statuses" :key="status.pivot.id">
                        <td>
                            <label :for="'status-date-'+status.id"><strong>{{status.name}}</strong></label>
                        </td>
                        <td class="form-inline">
                            <datepicker
                                :id="'status-date-'+status.id"
                                v-model="status.pivot.status_date"
                                input-class="form-control mr-2"
                                format='yyyy-MM-dd'
                                calendar-class="small-calendar"
                                placeholder="Select a date"
                                :highlighted="highlighted"
                                @selected="updateStatusDate(status.pivot,$event)"
                            ></datepicker>
                            <b-button @click="removeStatusEntry(status)"><strong>x</strong></b-button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </b-modal>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CurationStatusHistory from '../StatusHistory'
    import Datepicker from 'vuejs-datepicker'
    import moment from 'moment'

    export default {
        components: {
            CurationStatusHistory,
            Datepicker
        },
        props: {
            value: {
                required: true,
                type: Object
            }
        },
        data() {
            return {
                curationCopy: {},
                modalVisible: false,
                newStatusDate: new Date(),
                newStatusId: null,
                highlighted: {
                    from: new moment().hour(0),
                    to: new moment().hour(24)
                },
                statusDatesUpdated: false,
                errors: []
            }
        },
        watch: {
            value: {
                handler: 'syncCuration',
                immediate: true,
                deep: true
            }
        },
        computed: {
            ...mapGetters('curationStatuses', {
                curationStatuses: 'Items',
            }),
            statusOptions() {
                return this.curationStatuses.filter(status => user.canSelectCurationStatus(status, this.curationCopy))
            },
        },
        methods: {
            ...mapActions('curations', {
                linkNewStatus: 'linkNewStatus',
                storeStatusDate: 'updateStatusDate',
                unlinkStatus: 'unlinkStatus'
            }),
            addStatus() {
                this.linkNewStatus(
                    {
                        curation: this.curationCopy, 
                        data: {
                            curation_status_id: this.newStatusId,
                            status_date: this.$options.filters.formatDate(this.newStatusDate, 'YYYY-MM-DD')
                        }
                    }
                ).then(response => {
                    this.newStatusId = null,
                    this.newStatusDate = new Date()
                })
                .catch(response => {
                    this.errors = response.data.errors;
                })
            },
            updateStatusDate(pivot, newDate) {
                if (!pivot || moment(pivot.status_date).diff(newDate) == 0) {
                    return;
                }
                this.storeStatusDate({
                    curation: this.curationCopy,
                    pivotId: pivot.id,
                    statusDate: moment(newDate).format('YYYY-MM-DD')
                })
                .then(response => {
                    console.log('status date updated')
                })
                .catch(response => {
                    this.errors = response.data.errors;
                })
            },
            removeStatusEntry(status)
            {
                this.unlinkStatus({curation: this.curationCopy, pivotId: status.pivot.id})
            },
            submitAll() {
                if (this.newStatusId != null) {
                    this.addStatus();
                }
            },
            syncCuration() {
                this.curationCopy = JSON.parse(JSON.stringify(this.value))
            },
        },

    }
</script>