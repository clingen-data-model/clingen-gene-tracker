<style></style>

<template>
    <div>
        <button
            class="btn btn-info btn-sm form-control mb-2"
            @click="modalVisible = true"
        >Add or update classification</button>

        <classification-history :curation="value"></classification-history>

        <Dialog
            :visible.sync="modalVisible"
            header="Update Classification"
            :modal="true"
            :style="{width: '50vw'}"
            @hide="submitAll"
        >
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
                            <select class="form-control" v-model="newClassificationId">
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
                        <td class="form-inline">
                            <Calendar
                                v-model="newClassificationDate"
                                dateFormat="yy-mm-dd"
                                :showIcon="true"
                                placeholder="Select a date"
                                inputClass="form-control mr-2"
                            ></Calendar>
                            <button class="btn btn-primary" @click="addClassification">
                                <strong>+</strong>
                            </button>
                        </td>
                    </tr>
                    <tr v-for="classification in curationCopy.classifications" :key="classification.pivot.id">
                        <td>
                            <label :for="'classification-date-'+classification.id"><strong>{{classification.name}}</strong></label>
                        </td>
                        <td class="form-inline">
                            <Calendar
                                :id="'classification-date-'+classification.id"
                                v-model="classification.pivot.classification_date"
                                dateFormat="yy-mm-dd"
                                :showIcon="true"
                                placeholder="Select a date"
                                inputClass="form-control mr-2"
                                @date-select="updateclassificationDate(classification.pivot, $event)"
                            ></Calendar>
                            <button class="btn btn-secondary" @click="removeclassificationEntry(classification)"><strong>x</strong></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Dialog>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import ClassificationHistory from '../ClassificationHistory.vue'
    import moment from 'moment'
    import formatDate from '../../../helpers/formatDate'

    export default {
        components: {
            ClassificationHistory,
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
                newClassificationDate: new Date(),
                newClassificationId: null,
                classificationDatesUpdated: false,
                errors: {}
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
            ...mapGetters('classifications', {
                classifications: 'Items',
            }),
            classificationOptions() {
                return this.classifications
            },
        },
        methods: {
            formatDate,
            ...mapActions('curations', {
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
                if (!pivot || moment(pivot.classification_date).diff(newDate) == 0) {
                    return;
                }
                this.updateClassification({
                    curation: this.curationCopy,
                    pivotId: pivot.id,
                    data: {
                        classification_id: pivot.classification_id,
                        classification_date: moment(newDate).format('YYYY-MM-DD')}
                })
                .then(response => {
                    console.log('classification date updated')
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
                this.curationCopy = JSON.parse(JSON.stringify(this.value))
            },
        },
    }
</script>
