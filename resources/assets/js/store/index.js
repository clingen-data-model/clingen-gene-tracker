import Vue from 'vue'
import Vuex from 'vuex'
import messages from './modules/messages'
import curations from './modules/curations'
import panels from './modules/panels'
import users from './modules/users'
import curationStatuses from './modules/curation_statuses'
import rationales from './modules/rationales'
import workingGroups from './modules/working_groups'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

const state = {
  requestCount: 0,
}

const getters = {
  loading (state) {
    return state.requestCount > 0
  },
}

const mutations = {
  addRequest(state) {
    state.requestCount++;
  },
  removeRequest(state) {
    state.requestCount--;
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
  },
  actions: actions,
  strict: debug,
  // plugins: debug ? [createLogger()] : []
})
