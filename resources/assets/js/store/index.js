import Vue from 'vue'
import Vuex from 'vuex'
import messages from './modules/messages'
import curations from './modules/curations'
import panels from './modules/panels'
import users from './modules/users'
import curationStatuses from './modules/curation_statuses'
import rationales from './modules/rationales'
import workingGroups from './modules/working_groups'
import classifications from './modules/classifications'


Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

const state = {
  requestCount: 0,
  apiRequestCounts: {
    omim: 0,
    mondo: 0,
    pubmed: 0,
  }
}

const getters = {
  loading (state) {
    return state.requestCount > 0
  },
  apiLoading (state, apiKey) {
    if (typeof apiKey == 'object') {
      return false;
    }
    if (Object.keys(state.apiRequestCounts).indexOf(apiKey) < 0) {
      throw new Error(apiKey+' is not a valid key for apiRequestCounts.')
    }
    return state.apiRequestCounts[apiKey] > 0
  },
  omimLoading (state) {
    return state.apiRequestCounts['omim'] > 0
  }
}

const mutations = {
  addRequest(state) {
    state.requestCount++;
  },
  removeRequest(state) {
    state.requestCount--;
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
    state.apiRequestCounts[apiKey]--
  }  
}

const actions = {
}

export default new Vuex.Store({
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
  },
  actions: actions,
  strict: debug,
  // plugins: debug ? [createLogger()] : []
})
