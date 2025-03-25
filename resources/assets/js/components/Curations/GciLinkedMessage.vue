<template>
    <div>
        <small v-if="curation.gdm_uuid !== null">
            <slot name="gci-message">
                This precuration is linked to a 
                    <gci-link :curation="curation">GCI curation</gci-link>.  
                    Please update the {{attributeLabel}} 
                    <gci-link :curation="curation">there</gci-link>.
            </slot>
        </small>
        <span v-else>
            <slot></slot>
        </span>
    </div>
</template>
<script>
import GciLink from './GciLink.vue';

export default {
    name: 'GciLinkedMessage',
    props: {
        curation: {
            required: true,
            type: Object
        },
        attributeLabel: {
            type: String,
            required: false,
            default: 'this attribute'
        }
    },
    components: {
        GciLink
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>