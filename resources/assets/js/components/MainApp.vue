<template>
    <div>
        <div class="mt-2">
            <clingen-app></clingen-app>
            <b-progress
                :value="100"
                :max="100"
                animated
                v-show="loading"
                style="position:fixed; top:0; left:0; right:0; border-radius: 0"
                height="5px"
            ></b-progress>
        </div>
        <alerts></alerts>
    </div>
</template>
<script>
import ClingenApp from './ClingenApp.vue';
import Alerts from './Alerts.vue';

export default {
    name: 'MainApp',
    components: {
        'clingen-app': ClingenApp,
        'alerts': Alerts,
    },
    computed: {
        loading() {
            return this.$store.getters.loading;
        }
    }
}
</script>
