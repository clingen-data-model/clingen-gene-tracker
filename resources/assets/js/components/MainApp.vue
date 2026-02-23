<template>
    <div>
        <div class="mt-2">
            <clingen-app></clingen-app>
            <div
                v-show="loading"
                class="progress"
                style="position:fixed; top:0; left:0; right:0; border-radius:0; height:5px"
            >
                <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
            </div>
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
