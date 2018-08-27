
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import BootstrapVue from 'bootstrap-vue'
import store from './store/index'
import router from './routing.js'
import CriteriaTable from './components/Curations/CriteriaTable'
import User from './User'
import ExpertPanelField from './components/admin/ExpertPanelField'

window.Vue = require('vue')
window.Vue.use(BootstrapVue)

if (user) {
    user = new User(user);
}

axios.interceptors.request.use(function (config) {
    store.commit('addRequest');
    const apiParts = config.url.split(/[\/?&]/)
    try {
        store.commit('addApiRequest', apiParts[2])
    } catch (error) {        
    }
    return config;
})

axios.interceptors.response.use(
    function (response) {
        store.commit('removeRequest');
        const url = new URL(response.request.responseURL);
        const apiParts = url.pathname.split(/[\/?&]/)
        try {
            store.commit('removeApiRequest', apiParts[2])
        } catch (error) {
        }
        return response;
    }, 
    function (error) {
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
            'clingen-app': require('./components/ClingenApp.vue'),
            'clingen-nav': require('./components/ClingenNav.vue'),
            'alerts': require('./components/Alerts.vue'),
            CriteriaTable
        },
        computed: {
            loading: function () {
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