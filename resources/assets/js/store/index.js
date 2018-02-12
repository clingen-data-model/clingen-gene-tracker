import Vue from 'vue'
import Vuex from 'vuex'
import topics from './modules/topics'
import panels from './modules/panels'
import messages from './modules/messages'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

const state = {
}

const getters = {
}

const mutations = {
}

const actions = {
}

export default new Vuex.Store({
  state: state,
  getters: getters,
  mutations: mutations, 
  modules: {
    panels: panels,
    topics: topics,
    messages: messages
  },
  actions: actions,
  strict: debug,
  // plugins: debug ? [createLogger()] : []
})
