import { defineStore } from 'pinia'
import User from '../User'
import getCurrentUser from '../resources/users/get_current_user'

export const useAppStore = defineStore('app', {
    state: () => ({
        requestCount: 0,
        user: new User(window.user),
        maxUploadSize: window.maxUploadSize,
        supportedMimes: window.supportedMimes,
        apiRequestCounts: {
            omim: 0,
            mondo: 0,
            pubmed: 0,
        },
        features: {
            transferEnabled: false,
            sendToGciEnabled: false,
        },
    }),

    getters: {
        loading: (state) => state.requestCount > 0,
        apiLoading: (state) => {
            return (apiKey) => {
                if (typeof apiKey === 'object') {
                    return false
                }
                if (Object.keys(state.apiRequestCounts).indexOf(apiKey) < 0) {
                    throw new Error(apiKey + ' is not a valid key for apiRequestCounts.')
                }
                return state.apiRequestCounts[apiKey] > 0
            }
        },
        omimLoading: (state) => state.apiRequestCounts['omim'] > 0,
        getUser: (state) => state.user,
        getMaxUploadSize: (state) => state.maxUploadSize,
        getSupportedMimes: (state) => state.supportedMimes,
    },

    actions: {
        addRequest() {
            this.requestCount++
        },
        removeRequest() {
            this.requestCount--
        },
        addApiRequest(apiKey) {
            if (typeof apiKey === 'object') {
                return false
            }
            if (Object.keys(this.apiRequestCounts).indexOf(apiKey) < 0) {
                throw new Error(apiKey + ' is not a valid key for apiRequestCounts.')
            }
            this.apiRequestCounts[apiKey]++
        },
        removeApiRequest(apiKey) {
            if (typeof apiKey === 'object') {
                return false
            }
            if (Object.keys(this.apiRequestCounts).indexOf(apiKey) < 0) {
                throw new Error(apiKey + ' is not a valid key for apiRequestCounts.')
            }
            this.apiRequestCounts[apiKey]--
        },
        setUser(userData) {
            this.user = new User(userData)
        },
        setFeatures(features) {
            this.features = features
        },
        async fetchUser() {
            const user = await getCurrentUser()
            if (user) {
                this.setUser(user)
            }
        },
        async getFeatures() {
            const features = await window.axios
                .get('api/features')
                .then((response) => response.data)
                .catch((error) => {
                    console.error(error.response)
                })
            console.info('features', features)
            this.setFeatures(features)
        },
    },
})
