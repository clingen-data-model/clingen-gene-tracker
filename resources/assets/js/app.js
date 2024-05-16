/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap.js'
import Vue, { createApp }  from '@vue/compat'
window.Vue = Vue

import BootstrapVue from 'bootstrap-vue'
import store from './store/index'
import router from './routing.js'
// import configs from './configs.json';

// console.log(configs);

window.Vue.use(BootstrapVue)

import ExternalLink from './components/ExternalLink.vue'
window.Vue.component('external-link', ExternalLink)

import GciLink from './components/Curations/GciLink.vue';
window.Vue.component('gci-link', GciLink)
import GciLinkedMessage from './components/Curations/GciLinkedMessage.vue';
window.Vue.component('gci-linked-message', GciLinkedMessage)

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
    const app = createApp({
        router,
        el: '#app',
        store: store,
        components: {
            'clingen-app': () => import('@/components/ClingenApp.vue'),
            'clingen-nav': () => import('@/components/ClingenNav.vue'),
            'alerts': () => import('@/components/Alerts.vue'),
            CriteriaTable: () => import('@/components/Curations/CriteriaTable.vue'),
        },
        computed: {
            loading: function() {
                return this.$store.getters.loading;
            }
        }
    });
    app.config.globalProperties.$filters = {
        formatDate: function(dateString, format = 'YYYY-MM-DD HH:mm') {
            if (dateString === null) {
                return null;
            }

            return moment(dateString).format(format)
        }
    }
}

if (document.getElementById('expert-panel-field')) {
    const app = createApp({
        el: '#expert-panel-field',
        components: {
            ExpertPanelField: () => import('@/components/admin/ExpertPanelField.vue'),
        }
    });
}
