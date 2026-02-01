<style></style>

<template>
    <div>
        <button
            class="btn btn-info btn-sm form-control mb-2"
            @click="modalVisible = true"
        >Add or update status</button>

        <CurationStatusHistory :curation="modelValue"></CurationStatusHistory>

        <Dialog
            v-model:visible="modalVisible"
            header="Update Curation Status"
            modal
            @hide="submitAll"
        >
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
                            <select id="expert-panel-select" class="form-control" v-model="newStatusId">
                                <option :value="null">Select...</option>
                                <option v-for="status in statusOptions"
                                    :key="status.id"
                                    :value="status.id"
                                >
                                    {{status.name}}
                                </option>
                            </select>
                            <div class="text-danger" v-if="errors.curation_status_id">
                                <div v-for="message in errors.curation_status_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="d-flex align-items-center">
                            <div class="flex-grow-1 me-2">
                                <DatePicker
                                    v-model="newStatusDate"
                                    dateFormat="yy-mm-dd"
                                    showIcon
                                    inputClass="form-control"
                                    placeholder="Select a date"
                                />
                            </div>
                            <button
                                class="btn btn-primary"
                                @click="addStatus"
                            >
                                <strong>+</strong>
                            </button>
                        </td>
                    </tr>
                    <tr v-for="status in curationCopy.curation_statuses" :key="status.pivot.id">
                        <td>
                            <label :for="'status-date-'+status.id"><strong>{{status.name}}</strong></label>
                        </td>
                        <td class="d-flex align-items-center">
                            <div class="flex-grow-1 me-2">
                                <DatePicker
                                    :id="'status-date-'+status.id"
                                    :modelValue="parseDate(status.pivot.status_date)"
                                    dateFormat="yy-mm-dd"
                                    showIcon
                                    inputClass="form-control"
                                    placeholder="Select a date"
                                    @update:modelValue="updateStatusDate(status.pivot, $event)"
                                />
                            </div>
                            <button class="btn btn-secondary" @click="removeStatusEntry(status)"><strong>x</strong></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Dialog>
    </div>
</template>

<script>
    import { mapState, mapActions } from 'pinia'
    import { useAppStore } from '../../../stores/app'
    import { useCurationStatusesStore } from '../../../stores/curationStatuses'
    import { useCurationsStore } from '../../../stores/curations'
    import DatePicker from 'primevue/datepicker'
    import dayjs from 'dayjs'
    import Dialog from 'primevue/dialog'
    import CurationStatusHistory from '../StatusHistory.vue'
    import { formatDate } from '../../../utils/formatDate'

    export default {
        components: {
            CurationStatusHistory,
            DatePicker,
            Dialog
        },
        props: {
            modelValue: {
                required: true,
                type: Object
            }
        },
        emits: ['update:modelValue'],
        data() {
            return {
                curationCopy: {},
                modalVisible: false,
                newStatusDate: new Date(),
                newStatusId: null,
                statusDatesUpdated: false,
                errors: []
            }
        },
        watch: {
            modelValue: {
                handler: 'syncCuration',
                immediate: true,
                deep: true
            }
        },
        computed: {
            ...mapState(useAppStore, {user: 'getUser'}),
            ...mapState(useCurationStatusesStore, {
                curationStatuses: 'Items',
            }),
            statusOptions() {
                return this.curationStatuses.filter(status => this.user.canSelectCurationStatus(status, this.curationCopy))
            },
        },
        methods: {
            ...mapActions(useCurationsStore, {
                linkNewStatus: 'linkNewStatus',
                storeStatusDate: 'updateStatusDate',
                unlinkStatus: 'unlinkStatus'
            }),
            parseDate(dateStr) {
                if (!dateStr) return null;
                return new Date(dateStr);
            },
            addStatus() {
                this.linkNewStatus(
                    {
                        curation: this.curationCopy,
                        data: {
                            curation_status_id: this.newStatusId,
                            status_date: formatDate(this.newStatusDate, 'YYYY-MM-DD')
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
                if (!pivot || !newDate) {
                    return;
                }
                const formattedDate = dayjs(newDate).format('YYYY-MM-DD');
                if (dayjs(pivot.status_date).format('YYYY-MM-DD') === formattedDate) {
                    return;
                }
                this.storeStatusDate({
                    curation: this.curationCopy,
                    pivotId: pivot.id,
                    statusDate: formattedDate
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
                this.curationCopy = JSON.parse(JSON.stringify(this.modelValue))
            },
        },

    }
</script>
