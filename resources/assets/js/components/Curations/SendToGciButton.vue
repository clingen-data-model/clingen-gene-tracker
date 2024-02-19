<script>
import moment from 'moment';
import {mapActions} from 'vuex'
import queryStringFromParams from '../../http/query_string_from_params';

export default {
    name: 'ComponentName',
    props: {
        curation: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        enabled: function () {
            return this.curation.hgnc_id 
                && this.curation.disease 
                && this.curation.mode_of_inheritance
                && !this.curation.gdm_uuid;
        },
        popoverText () {
            if (!this.enabled) {
                const reason = (this.curation.gdm_uuid) 
                    ? 'the curation is already associatd with a GCI record.' 
                    : ' the curation is not complete.';
                return `Disabled because ${reason}`
            }
            return null;
        }
    },
    watch: {
        curation: {
            deep: true,
            immediate: true,
            handler: function () {
            }
        }
    },
    methods: {
        ...mapActions('curations', {
            // fetchCuration: 'fetchItem',
            // storeNewItem: 'storeNewItem',
            storeItemUpdates: 'storeItemUpdates',
            linkNewStatus: 'linkNewStatus',
        }),
        async handleClick () {
            await this.storeItemUpdates(this.curation);
            await this.linkNewStatus({
                    curation: this.curation, 
                    data: {
                        curation_status_id: 4,
                        status_date: moment().format('YYYY-MM-DD')
                    }
                }).then(rsp => {
                    console.log('should have linked new status');
                });
            this.$emit('saved');

            this.redirectToGciCreationForm();

        },
        redirectToGciCreationForm () {
            const params = {
                aff: this.curation.expert_panel.affiliation.clingen_id,
                gtid: this.curation.uuid,
                gene: this.curation.gene_symbol,
                disease: this.curation.mondo_id,
                moi: this.curation.mode_of_inheritance.hp_id
            }

            const url = `https://curation.clinicalgenome.org/create-gene-disease${queryStringFromParams(params)}`
            console.log(url);
            window.open(url, '_gci');
        }
    }
}
</script>

<template>
    <div v-if="$store.state.features.sendToGciEnabled">
        <span id="send-to-gci-button">            
            <button class="btn btn-primary btn-lg" 
                :disabled="!enabled" 
                @click="handleClick"
                :title="popoverText"
            >
                Complete PreCuration and Go to GCI
            </button>
        </span>
        <b-popover target="send-to-gci-button" triggers="hover" placement="top" v-if="!enabled">
            {{popoverText}}
        </b-popover>
    </div>
</template>
