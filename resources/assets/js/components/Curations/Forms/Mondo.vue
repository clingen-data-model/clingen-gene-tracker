<style></style>
<template>
    <div>
        <b-form-group horizontal label="MonDO ID" label-for="mondo-id" class="position-relative"
            :class="{'error': errors.mondo_id}"
        >
            <search-select 
                :value="updatedCuration.disease"
                @input="updateCurationDisease"
                :search-function="searchMondo"
                style="z-index: 2"
                placeholder="MonDO ID or name"
                :disabled="(updatedCuration.gdm_uuid !== null)"
            >
                <template v-slot:selection-label="{selection}">
                    <div v-if="typeof selection == 'object'">
                        {{selection.mondo_id}} - {{selection.name}}
                    </div>
                    <div v-else>{{selection}}</div>
                </template>
                <template v-slot:option="{option}">
                    <div v-if="typeof option == 'object'">
                        {{option.mondo_id}} - {{option.name}}
                    </div>
                    <div v-else>
                        {{option}}
                    </div>
                </template>
            </search-select>
            <validation-error :messages="errors.mondo_id"></validation-error>
            <gci-linked-message :curation="updatedCuration" attribute-label="MonDO ID">
                <small class="text-muted">
                    Alternatively, refer to <a href="https://www.ebi.ac.uk/ols/ontologies/mondo" target="mondo">MonDO</a> for a valid MonDO ID
                </small>
            </gci-linked-message>
            <!-- <small class="text-muted" v-else>
                This precuration is linked to a <a target="gci" :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`">GCI record</a>.  Please update the MonDO ID <a target="gci" :href="`https://curation.clinicalgenome.org/curation-central/${updatedCuration.gdm_uuid}`">there</a>.
            </small> -->

            <!-- <mondo-alert v-if="updatedCuration.disease" :curation="updatedCuration"></mondo-alert> -->
            <curation-notification :curation="updatedCuration" :search-by-mondo="true"/>

        </b-form-group>
        or
        <b-form-group horizontal
            :class="{'error': errors.disease_entity_notes}"
        >
            <template v-slot:label>
                Disease Entity (<small>Use when no appropriate MonDO ID is available.</small>)
            </template>
            <textarea v-model="updatedCuration.disease_entity_notes" class="form-control" />
            <transition name="fade">
                <div v-if="updatedCuration.disease_entity_notes" class="alert alert-info mt-2">
                    <a href="https://github.com/monarch-initiative/mondo/issues/new/choose" target="mondo">Request a new MonDO term</a> by submitting an issue on their <a href="https://github.com/monarch-initiative/mondo">GitHub project.</a> (GitHub account required)
                </div>
            </transition>
            
            <!-- ADD CONDITION LATER -->
                <div class="alert alert-info mt-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div><strong>No appropriate MonDO ID?</strong> You can submit a new MonDO term request from GeneTracker.</div>
                        <b-button size="sm" variant="primary" @click="openMondoRequestModal">Request new MonDO term</b-button>
                    </div>
                    <div v-if="mondoRequestsLoading" class="mt-2 text-muted">Loading requests...</div>
                    <div v-else-if="mondoRequests.length" class="mt-2">
                        <div class="small text-muted mb-1">Previous requests for this curation:</div>

                        <ul class="mb-0 pl-3">
                            <li v-for="r in mondoRequests" :key="r.uuid" class="mb-1">
                            <span class="font-weight-bold">{{ r.title }}</span>
                            <span class="ml-2 badge" :class="badgeClass(r)">
                                {{ r.github_state || r.status }}
                            </span>

                            <template v-if="r.github_issue_url">
                                — <a :href="r.github_issue_url" target="_blank">GitHub #{{ r.github_issue_number }}</a>
                            </template>

                            <template v-if="r.status === 'failed' && r.last_error">
                                <div class="text-danger small mt-1">{{ r.last_error }}</div>
                            </template>
                            </li>
                        </ul>
                    </div>

                    <!-- Modal -->
                    <b-modal class="lg" id="mondo-request-modal" ref="mondoRequestModal" title="Request a new MonDO term" ok-title="Submit request" cancel-title="Cancel" @ok="submitMondoRequest">
                        <div class="mb-2 small text-muted">
                            This will create a GitHub issue using the standard template. Your request will be tracked in GeneTracker.
                        </div>

                        <b-form-group label="Causal gene" description="Auto-filled from this curation">
                            <div class="small">
                            <div><strong>{{ updatedCuration.gene_symbol }}</strong></div>
                            <div>HGNC: <strong>{{ updatedCuration.hgnc_id }}</strong></div>
                            </div>
                        </b-form-group>

                        <b-form-group label="Reference for this gene–disease association (PMIDs)" description="Pipe separated: PMID:123|PMID:456">
                            <b-form-textarea
                            v-model="mondoForm.association_references"
                            rows="2"
                            placeholder="PMID:123|PMID:567"
                            />
                            <div class="small text-muted mt-1">
                            Auto from GT: {{ autoPmidsText }}
                            <b-button size="sm" variant="link" class="p-0 ml-1" @click="useAutoPmids">Use</b-button>
                            </div>
                        </b-form-group>

                        <b-form-group label="Parent term" description="The MonDO term under which this new term will be classified">
                            <b-form-input v-model="mondoForm.parent_term" placeholder="exudative vitreoretinopathy"></b-form-input>
                        </b-form-group>

                        <b-form-group label="Parent term ID" description="MONDO:####">
                            <b-form-input v-model="mondoForm.parent_term_id" placeholder="MONDO:0019516"></b-form-input>
                        </b-form-group>

                        <b-form-group label="Requested label (optional)">
                            <b-form-input v-model="mondoForm.requested_label" placeholder="FZD4-related exudative vitreoretinopathy"></b-form-input>
                        </b-form-group>

                        <b-form-group label="Definition additions / suggested definition (optional)">
                            <b-form-textarea v-model="mondoForm.definition_additions" rows="3" placeholder="This disease is characterized by..."></b-form-textarea>
                        </b-form-group>

                        <b-form-group label="Cross references (optional)" description="OMIM, Orphanet, etc.">
                            <b-form-textarea v-model="mondoForm.cross_references" rows="2" placeholder="OMIM:123456"></b-form-textarea>
                        </b-form-group>

                        <div v-if="mondoSubmitResult" class="alert alert-success mt-3 mb-0">
                            Created issue:
                            <a :href="mondoSubmitResult.github_issue_url" target="_blank">
                            GitHub #{{ mondoSubmitResult.github_issue_number }}
                            </a>
                        </div>

                        <div v-if="mondoSubmitError" class="alert alert-danger mt-3 mb-0">
                            {{ mondoSubmitError }}
                        </div>
                    </b-modal>
                </div>

        </b-form-group>

        <send-to-gci-button :curation="updatedCuration" @saved="emitUpdated"/>

    </div>
</template>
<script>
    import curationFormMixin from '../../../mixins/curation_form_mixin'
    import CurationNotification from './ExistingCurationNotification.vue'
    import ValidationError from '../../ValidationError.vue'
    import SearchSelect from '../../forms/SearchSelect.vue'
    import SendToGciButton from '../SendToGciButton.vue'

    export default {
        mixins: [
            curationFormMixin, // handles syncing of prop value to updatedCuration
        ],
        components: {
            ValidationError,
            CurationNotification,
            SearchSelect,
            SendToGciButton,
        },
        data: function () {
            return {
                page: 'mondo',
                updatedCuration: {},
                selection: 'eat',
                searchMondo: async (searchText) => {
                    return await window.axios.get('/api/diseases/search?query_string='+searchText)
                        .then(response => {
                            return response.data;
                        });
                },
                mondoRequests: [],
                mondoRequestsLoading: false,

                mondoForm: {
                    association_references: '',
                    parent_term: '',
                    parent_term_id: '',
                    requested_label: '',
                    definition_additions: '',
                    cross_references: '',
                },
                mondoSubmitResult: null,
                mondoSubmitError: null,
            }
        },
        watch: {
            updatedCuration: function () {
                this.emitUpdated()
            },
            value: function (to, from) {
                if (this.value != this.updatedCuration) {
                    this.syncValue();
                }
            },
            'updatedCuration.id': function () { this.loadMondoRequests() },
        },
        methods: {
            updateCurationDisease: function (value) {
                console.log(value);
                this.updatedCuration.disease = value;
                this.updatedCuration.mondo_id = value ? value.mondo_id : null;
                this.emitUpdated()
            },
            emitUpdated () {
                this.$emit('input', this.updatedCuration)
            },
            openMondoRequestModal () {
                this.mondoSubmitResult = null
                this.mondoSubmitError = null

                if (this.updatedCuration?.disease?.name) {
                    this.mondoForm.parent_term = this.updatedCuration.disease.name
                }
                if (this.updatedCuration?.mondo_id) {
                    this.mondoForm.parent_term_id = this.updatedCuration.mondo_id
                }
                if (!this.mondoForm.association_references) {
                    this.mondoForm.association_references = this.autoPmidsText === '(none)' ? '' : this.autoPmidsText
                }

                this.$refs.mondoRequestModal.show()
            },

            useAutoPmids () {
                this.mondoForm.association_references = this.autoPmidsText === '(none)' ? '' : this.autoPmidsText
            },

            async loadMondoRequests () {
                const curationId = this.updatedCuration?.id
                if (!curationId) return

                this.mondoRequestsLoading = true
                try {
                    const resp = await window.axios.get(`/api/curations/${curationId}/mondo-requests`)
                    this.mondoRequests = resp.data?.mondo_requests || []
                } catch (e) {
                    this.mondoRequests = []
                } finally {
                    this.mondoRequestsLoading = false
                }
            },

            badgeClass (r) {
                const state = (r.github_state || '').toLowerCase()
                if (state === 'open') return 'badge-success'
                if (state === 'closed') return 'badge-secondary'
                if (r.status === 'failed') return 'badge-danger'
                return 'badge-light'
            },

            async submitMondoRequest (bvModalEvt) {
                bvModalEvt.preventDefault()

                const curationId = this.updatedCuration?.id
                if (!curationId) {
                    this.mondoSubmitError = 'Missing curation id.'
                    return
                }

                this.mondoSubmitError = null
                this.mondoSubmitResult = null

                const payload = {                    
                    parent_term: this.mondoForm.parent_term || null,
                    parent_term_id: this.mondoForm.parent_term_id || null,
                    association_references: this.mondoForm.association_references || null,
                    requested_label: this.mondoForm.requested_label || null,
                    definition_additions: this.mondoForm.definition_additions || null,
                    cross_references: this.mondoForm.cross_references || null,
                    child_terms: [],
                }

                try {
                    const resp = await window.axios.post(`/api/curations/${curationId}/mondo-requests/new-term`, payload)
                    this.mondoSubmitResult = resp.data
                    await this.loadMondoRequests()
                    this.$nextTick(() => this.$refs.mondoRequestModal.hide())
                } catch (e) {
                    this.mondoSubmitError = e?.response?.data?.message || e?.message || 'Failed to submit request.'
                }
            },

       },
       computed: {
            autoPmidsText () {
                const pmids = this.updatedCuration?.pmids
                if (!pmids || !Array.isArray(pmids) || pmids.length === 0) return '(none)'
                return pmids.map(p => `PMID:${String(p).replace(/^PMID:/i,'')}`).join('|')
            }
        },
        mounted () {
            this.loadMondoRequests()
        },

    }
</script>