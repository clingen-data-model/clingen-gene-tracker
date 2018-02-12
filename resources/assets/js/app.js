
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import BootstrapVue from 'bootstrap-vue'
import store from './store/index'

window.Vue = require('vue')
window.Vue.use(BootstrapVue)


const app = new Vue({
    el: '#app',
    store: store,
    components: {
        'clingen-app': require('./components/ClingenApp.vue'),
        'clingen-nav': require('./components/ClingenNav.vue'),
    },
});
