/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap'
import Vue from 'vue'
import PrimeVue from 'primevue/config'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Calendar from 'primevue/calendar'
import 'primevue/resources/themes/lara-light-blue/theme.css'
import 'primevue/resources/primevue.min.css'
import 'primeicons/primeicons.css'
import store from './store/index'
import router from './routing.js'
import User from './User'
import ExpertPanelField from './components/admin/ExpertPanelField.vue'
import ExternalLink from './components/ExternalLink.vue'
import GciLink from './components/Curations/GciLink.vue'
import GciLinkedMessage from './components/Curations/GciLinkedMessage.vue'
import CriteriaTable from './components/Curations/CriteriaTable.vue'
import MainApp from './components/MainApp.vue'

window.Vue = Vue
window.Vue.use(PrimeVue)
window.Vue.component('DataTable', DataTable)
window.Vue.component('Column', Column)
window.Vue.component('Dialog', Dialog)
window.Vue.component('TabView', TabView)
window.Vue.component('TabPanel', TabPanel)
window.Vue.component('Calendar', Calendar)

window.Vue.component('external-link', ExternalLink)
window.Vue.component('gci-link', GciLink)
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
        } catch (error) {
            console.log(error)
        }
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
        store,
        render: h => h(MainApp),
    });
}

if (document.getElementById('criteria-app')) {
    new Vue({
        el: '#criteria-app',
        store,
        render: h => h(CriteriaTable),
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
