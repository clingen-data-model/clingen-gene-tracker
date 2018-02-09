
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import BootstrapVue from 'bootstrap-vue'

window.Vue = require('vue');
window.Vue.use(BootstrapVue);
window.Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

window.Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

window.Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    components: {
        'clingen-app': require('./components/ClingenApp.vue'),
        'clingen-nav': require('./components/ClingenNav.vue'),
    },
    methods: {
    }
});
