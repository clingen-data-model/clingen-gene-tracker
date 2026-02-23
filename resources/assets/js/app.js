/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap'
import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import Lara from '@primevue/themes/lara'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DatePicker from 'primevue/datepicker'
import 'primeicons/primeicons.css'
import store from './store/index'
import router from './routing.js'
import ExpertPanelField from './components/admin/ExpertPanelField.vue'
import ExternalLink from './components/ExternalLink.vue'
import GciLink from './components/Curations/GciLink.vue'
import GciLinkedMessage from './components/Curations/GciLinkedMessage.vue'
import CriteriaTable from './components/Curations/CriteriaTable.vue'
import MainApp from './components/MainApp.vue'

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

function registerPrimeVueComponents(app) {
    app.use(PrimeVue, { theme: { preset: Lara } })
    app.component('DataTable', DataTable)
    app.component('Column', Column)
    app.component('Dialog', Dialog)
    app.component('TabView', TabView)
    app.component('TabPanel', TabPanel)
    app.component('DatePicker', DatePicker)
}

if (document.getElementById('app')) {
    const app = createApp(MainApp)
    app.use(store)
    app.use(router)
    registerPrimeVueComponents(app)
    app.component('external-link', ExternalLink)
    app.component('gci-link', GciLink)
    app.component('gci-linked-message', GciLinkedMessage)
    app.mount('#app')
}

if (document.getElementById('criteria-app')) {
    const app = createApp(CriteriaTable)
    app.use(store)
    registerPrimeVueComponents(app)
    app.mount('#criteria-app')
}

if (document.getElementById('expert-panel-field')) {
    const app = createApp({ components: { ExpertPanelField } })
    app.mount('#expert-panel-field')
}
