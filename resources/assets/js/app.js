
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import BootstrapVue from 'bootstrap-vue'
import store from './store/index'
import router from './routing.js'

window.Vue = require('vue')
window.Vue.use(BootstrapVue)


window.axios.interceptors.request.use(function (config) {
    store.commit('addRequest');
    return config;
})

axios.interceptors.response.use(function (response) {
    store.commit('removeRequest');
    return response;
  }, function (error) {
    // Do something with response error
    store.commit('removeRequest');
    return Promise.reject(error);
  });

if (document.getElementById('app')) {
    const app = new Vue({
        router,
        el: '#app',
        store: store,
        components: {
            'clingen-app': require('./components/ClingenApp.vue'),
            'clingen-nav': require('./components/ClingenNav.vue'),
            'alerts': require('./components/Alerts.vue')
        },
        computed: {
            loading: function () {
                return this.$store.getters.loading;
            }
        }
    });
}