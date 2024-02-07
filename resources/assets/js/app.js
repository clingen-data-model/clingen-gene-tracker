/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import BootstrapVue, { componentsPlugin } from 'bootstrap-vue'
import store from './store/index'
import router from './routing.js'
import CriteriaTable from './components/Curations/CriteriaTable.vue'
import User from './User'
import ExpertPanelField from './components/admin/ExpertPanelField.vue'
import './filters.js';
// import configs from './configs.json';

// console.log(configs);

window.Vue = require('vue').default
window.Vue.use(BootstrapVue)

import ExternalLink from './components/ExternalLink.vue'
window.Vue.component('external-link', ExternalLink)

import GciLink from './components/Curations/GciLink.vue';
window.Vue.component('gci-link', GciLink)
import GciLinkedMessage from './components/Curations/GciLinkedMessage.vue';
window.Vue.component('gci-linked-message', GciLinkedMessage)

if (user) {
    user = new User(user);
}

axios.interceptors.request.use(function(config) {
    store.commit('addRequest');
    const apiParts = config.url.split(/[\/?&]/)
    try {
        store.commit('addApiRequest', apiParts[2])
    } catch (error) {}
    return config;
})

axios.interceptors.response.use(
    function(response) {
        store.commit('removeRequest');
        const url = new URL(response.request.responseURL);
        const apiParts = url.pathname.split(/[\/?&]/)
        try {
            store.commit('removeApiRequest', apiParts[2])
        } catch (error) {}
        return response;
    },
    function(error) {
        store.commit('removeRequest');
        const url = new URL(error.response.request.responseURL);
        const apiParts = url.pathname.split(/[\/?&]/)
        try {
            store.commit('removeApiRequest', apiParts[2])
        } catch (error) {
            console.log(error)
        }
        return Promise.reject(error);
    }
);

if (document.getElementById('app')) {
    const app = new Vue({
        router,
        el: '#app',
        store: store,
        components: {
            'clingen-app': require('./components/ClingenApp.vue').default,
            'clingen-nav': require('./components/ClingenNav.vue').default,
            'alerts': require('./components/Alerts.vue').default,
            CriteriaTable
        },
        computed: {
            loading: function() {
                return this.$store.getters.loading;
            }
        }
    });
}

if (document.getElementById('expert-panel-field')) {
    const app = new Vue({
        el: '#expert-panel-field',
        components: {
            ExpertPanelField
        }
    });
}