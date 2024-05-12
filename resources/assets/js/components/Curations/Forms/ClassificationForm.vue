<style></style>

<template>
    <div>
        <b-button 
            variant="info"
            size="sm" 
            class="form-control mb-2"
            @click="modalVisible = true"
        >Add or update classification</b-button>

        <classification-history :curation="value"></classification-history>

        <b-modal 
            v-model="modalVisible"
            @hide="submitAll"
            size="lg"
        >
            <div slot="modal-header">
                <h3>Update Classification</h3>
            </div>    
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
                            <b-form-select v-model="newClassificationId">
                                <option :value="null">Select...</option>
                                <option v-for="classification in classificationOptions"
                                    :key="classification.id"
                                    :value="classification.id"
                                >
                                    {{classification.name}}
                                </option>
                            </b-form-select>
                            <div class="text-danger" v-if="errors.classification_id">
                                <div v-for="message in errors.classification_id" :key="message"><small>{{message}}</small></div>
                            </div>
                        </td>
                        <td class="form-inline">
                            <datepicker 
                                v-model="newClassificationDate"
                                input-class="form-control mr-2"
                                format='yyyy-MM-dd'
                                calendar-class="small-calendar"
                                placeholder="Select a date"
                                :highlighted="highlighted"
                            ></datepicker>
                            <b-button 
                                variant="primary"
                                @click="addClassification"
                            >
                                <strong>+</strong>
                            </b-button>
                        </td>
                    </tr>
                    <tr v-for="classification in curationCopy.classifications" :key="classification.pivot.id">
                        <td>
                            <label :for="'classification-date-'+classification.id"><strong>{{classification.name}}</strong></label>
                        </td>
                        <td class="form-inline">
                            <datepicker
                                :id="'classification-date-'+classification.id"
                                v-model="classification.pivot.classification_date"
                                input-class="form-control mr-2"
                                format='yyyy-MM-dd'
                                calendar-class="small-calendar"
                                placeholder="Select a date"
                                :highlighted="highlighted"
                                @selected="updateclassificationDate(classification.pivot,$event)"
                            ></datepicker>
                            <b-button @click="removeclassificationEntry(classification)"><strong>x</strong></b-button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </b-modal>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import ClassificationHistory from '../ClassificationHistory.vue'
    import Datepicker from 'vuejs-datepicker'
    import moment from 'moment'

    export default {
        components: {
            ClassificationHistory,
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
                newClassificationDate: new Date(),
                newClassificationId: null,
                highlighted: {
                    from: new moment().hour(0),
                    to: new moment().hour(24)
                },
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
                            classification_date: this.$filters.formatDate(this.newClassificationDate, 'YYYY-MM-DD')
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