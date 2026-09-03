import { createStore } from 'vuex'
import messages from './modules/messages'
import curations from './modules/curations'
import panels from './modules/panels'
import users from './modules/users'
import curationStatuses from './modules/curation_statuses'
import rationales from './modules/rationales'
import workingGroups from './modules/working_groups'
import classifications from './modules/classifications'
import mois from './modules/mois'
import User from '../User'
import getCurrentUser from '../resources/users/get_current_user';

const debug = import.meta.env.DEV

const state = {
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
        sendToGciEnabled: false
    }
}

const getters = {
    loading(state) {
        return state.requestCount > 0
    },
    omimLoading(state) {
        return state.apiRequestCounts['omim'] > 0
    },
    getUser: state => state.user,
    getMaxUploadSize: state => state.maxUploadSize,
    getSupportedMimes: state => state.supportedMimes
}

const mutations = {
    setUser(state, user) {
        state.user = new User(user)
    },
    addRequest(state) {
        state.requestCount++;
    },
    removeRequest(state) {
        if (state.requestCount > 0) {
            state.requestCount--;
        }
    },
    addApiRequest(state, apiKey) {
        if (typeof apiKey == 'object') {
            return false;
        }
        if (Object.keys(state.apiRequestCounts).indexOf(apiKey) < 0) {
            throw new Error(apiKey + ' is not a valid key for apiRequestCounts.')
        }
        state.apiRequestCounts[apiKey]++
    },
    removeApiRequest(state, apiKey) {
        if (typeof apiKey == 'object') {
            return false;
        }
        if (Object.keys(state.apiRequestCounts).indexOf(apiKey) < 0) {
            throw new Error(apiKey + ' is not a valid key for apiRequestCounts.')
        }
        if (state.apiRequestCounts[apiKey] > 0) {
            state.apiRequestCounts[apiKey]--
        }
    },
    setFeatures(state, features) {
        state.features = features;
    }
}

const actions = {
    async fetchUser({ commit }) {
        const user = await getCurrentUser();
        if (user) {
            commit('setUser', user);
        }
    },
    async getFeatures({ commit }) {
        try {
            const response = await window.axios.get('api/features');
            commit('setFeatures', response.data);
        } catch (error) {
            console.error(error.response);
        }
    }
}

export default createStore({
    state: state,
    getters: getters,
    mutations: mutations,
    modules: {
        messages: messages,
        panels: panels,
        curations: curations,
        curationStatuses: curationStatuses,
        users: users,
        rationales: rationales,
        workingGroups: workingGroups,
        classifications: classifications,
        mois: mois,
    },
    actions: actions,
    strict: debug,
    // plugins: debug ? [createLogger()] : []
})
