<style></style>

<template>
    <div>
        <button
            class="btn btn-info btn-sm form-control mb-2"
            @click="modalVisible = true"
        >Add or update classification</button>

        <classification-history :curation="modelValue"></classification-history>

        <Dialog
            v-model:visible="modalVisible"
            @hide="submitAll"
            :style="{ width: '50rem' }"
            :modal="true"
        >
            <template #header>
                <h3>Update Classification</h3>
            </template>
            <table class="table">
                <thead>
                    <tr>
                        <th>Classification</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select v-model="newClassificationId" class="form-control">
                                <option :value="null">Select...</option>
                                <option v-for="classification in classificationOptions"
                                    :key="classification.id"
                                    :value="classification.id"
                                >
                                    {{classification.name}}
                                </option>
                            </select>
                            <div class="text-danger" v-if="errors.classification_id">
                                <div v-for="message in errors.classification_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="d-flex align-items-center">
                            <DatePicker
                                v-model="newClassificationDate"
                                dateFormat="yy-mm-dd"
                                placeholder="Select a date"
                                inputClass="form-control me-2"
                            />
                            <button
                                class="btn btn-primary"
                                @click="addClassification"
                            >
                                <strong>+</strong>
                            </button>
                        </td>
                    </tr>
                    <tr v-for="classification in curationCopy.classifications" :key="classification.pivot.id">
                        <td>
                            <label :for="'classification-date-'+classification.id"><strong>{{classification.name}}</strong></label>
                        </td>
                        <td class="d-flex align-items-center">
                            <DatePicker
                                :inputId="'classification-date-'+classification.id"
                                v-model="classification.pivot.classification_date"
                                dateFormat="yy-mm-dd"
                                placeholder="Select a date"
                                inputClass="form-control me-2"
                                @date-select="updateclassificationDate(classification.pivot, $event)"
                            />
                            <button class="btn btn-secondary" @click="removeclassificationEntry(classification)"><strong>x</strong></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Dialog>
    </div>
</template>

<script>
    import { mapState, mapActions } from 'pinia'
    import { useClassificationsStore } from '../../../stores/classifications'
    import { useCurationsStore } from '../../../stores/curations'
    import ClassificationHistory from '../ClassificationHistory.vue'
    import DatePicker from 'primevue/datepicker'
    import Dialog from 'primevue/dialog'
    import dayjs from 'dayjs'
    import { formatDate } from '../../../utils/formatDate'

    export default {
        components: {
            ClassificationHistory,
            DatePicker,
            Dialog,
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
                newClassificationDate: new Date(),
                newClassificationId: null,
                classificationDatesUpdated: false,
                errors: {}
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
            ...mapState(useClassificationsStore, {
                classifications: 'Items',
            }),
            classificationOptions() {
                return this.classifications
            },
        },
        methods: {
            ...mapActions(useCurationsStore, {
                linkNewClassification: 'linkNewClassification',
                updateClassification: 'updateClassification',
                unlinkClassification: 'unlinkClassification'
            }),
            addClassification() {
                this.linkNewClassification(
                    {
                        curation: this.curationCopy,
                        data: {
                            classification_id: this.newClassificationId,
                            classification_date: formatDate(this.newClassificationDate, 'YYYY-MM-DD')
                        }
                    }
                ).then(response => {
                    this.newClassificationId = null,
                    this.newClassificationDate = new Date()
                })
                .catch(error => {
                    this.errors = error.response.data.errors;
                })
            },
            updateclassificationDate(pivot, newDate) {
                if (!pivot || dayjs(pivot.classification_date).diff(newDate) == 0) {
                    return;
                }
                this.updateClassification({
                    curation: this.curationCopy,
                    pivotId: pivot.id,
                    data: {
                        classification_id: pivot.classification_id,
                        classification_date: dayjs(newDate).format('YYYY-MM-DD')}
                })
                .catch(response => {
                    this.errors = response.data.errors;
                })
            },
            removeclassificationEntry(classification)
            {
                this.unlinkClassification({curation: this.curationCopy, pivotId: classification.pivot.id})
            },
            submitAll() {
                if (this.newClassificationId != null) {
                    this.addClassification();
                }
            },
            syncCuration() {
                this.curationCopy = JSON.parse(JSON.stringify(this.modelValue))
            },
        },

    }
</script>
