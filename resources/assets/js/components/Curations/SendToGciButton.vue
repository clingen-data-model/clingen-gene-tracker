<script>
import dayjs from 'dayjs';
import { mapActions } from 'pinia'
import { useCurationsStore } from '../../stores/curations'
import { useAppStore } from '../../stores/app'
import queryStringFromParams from '../../http/query_string_from_params';

export default {
    name: 'SendToGciButton',
    props: {
        curation: {
            type: Object,
            required: true
        }
    },
    emits: ['saved'],
    data() {
        return {

        }
    },
    computed: {
        sendToGciEnabled() {
            const appStore = useAppStore()
            return appStore.features.sendToGciEnabled
        },
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
    methods: {
        ...mapActions(useCurationsStore, {
            storeItemUpdates: 'storeItemUpdates',
            linkNewStatus: 'linkNewStatus',
        }),
        async handleClick () {
            await this.storeItemUpdates(this.curation);
            await this.linkNewStatus({
                    curation: this.curation,
                    data: {
                        curation_status_id: 4,
                        status_date: dayjs().format('YYYY-MM-DD')
                    }
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
            window.open(url, '_gci');
        }
    }
}
</script>

<template>
    <div v-if="sendToGciEnabled">
        <span id="send-to-gci-button">
            <button class="btn btn-primary btn-lg"
                :disabled="!enabled"
                @click="handleClick"
                :title="popoverText"
            >
                Complete PreCuration and Go to GCI
            </button>
        </span>
    </div>
</template>
