import Vue from 'vue'
import Vuex from 'vuex'
import messages from './modules/messages'
import topics from './modules/topics'
import panels from './modules/panels'
import users from './modules/users'
import topicStatuses from './modules/topic_statuses'
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
    topics: topics,
    topicStatuses: topicStatuses,
    users: users,
    rationales: rationales,
    workingGroups: workingGroups,
  },
  actions: actions,
  strict: debug,
  // plugins: debug ? [createLogger()] : []
})
