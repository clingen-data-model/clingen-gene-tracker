import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import 'primeicons/primeicons.css'
import router from './routing.js'
import { useAppStore } from './stores/app'
import { formatDate } from './utils/formatDate'
import User from './User'

import ClingenApp from './components/ClingenApp.vue'
import ClingenNav from './components/ClingenNav.vue'
import Alerts from './components/Alerts.vue'
import CriteriaTable from './components/Curations/CriteriaTable.vue'
import ExternalLink from './components/ExternalLink.vue'
import GciLink from './components/Curations/GciLink.vue'
import GciLinkedMessage from './components/Curations/GciLinkedMessage.vue'
import ExpertPanelField from './components/admin/ExpertPanelField.vue'

if (window.user) {
    window.user = new User(window.user)
}

if (document.getElementById('app')) {
    const pinia = createPinia()
    const app = createApp({
        components: {
            'clingen-app': ClingenApp,
            'clingen-nav': ClingenNav,
            alerts: Alerts,
            CriteriaTable,
        },
        computed: {
            loading() {
                const appStore = useAppStore()
                return appStore.loading
            },
        },
    })

    app.use(pinia)
    app.use(router)
    app.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                darkModeSelector: false,
            },
        },
    })

    app.component('external-link', ExternalLink)
    app.component('gci-link', GciLink)
    app.component('gci-linked-message', GciLinkedMessage)

    app.config.globalProperties.$formatDate = formatDate

    // Set up axios interceptors with Pinia store
    const appStore = useAppStore()

    window.axios.interceptors.request.use(function (config) {
        appStore.addRequest()
        const apiParts = config.url.split(/[\/?&]/)
        try {
            appStore.addApiRequest(apiParts[2])
        } catch (error) {}
        return config
    })

    window.axios.interceptors.response.use(
        function (response) {
            appStore.removeRequest()
            const url = new URL(response.request.responseURL)
            const apiParts = url.pathname.split(/[\/?&]/)
            try {
                appStore.removeApiRequest(apiParts[2])
            } catch (error) {}
            return response
        },
        function (error) {
            appStore.removeRequest()
            const url = new URL(error.response.request.responseURL)
            const apiParts = url.pathname.split(/[\/?&]/)
            try {
                appStore.removeApiRequest(apiParts[2])
            } catch (error) {
                console.log(error)
            }
            return Promise.reject(error)
        }
    )

    app.mount('#app')
}

if (document.getElementById('expert-panel-field')) {
    const pinia = createPinia()
    const epApp = createApp({
        components: {
            ExpertPanelField,
        },
    })
    epApp.use(pinia)
    epApp.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                darkModeSelector: false,
            },
        },
    })
    epApp.mount('#expert-panel-field')
}
