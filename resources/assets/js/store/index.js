import Vue from 'vue'
import Vuex from 'vuex'
import genes from './modules/genes'
import panels from './modules/panels'

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
    genes: genes
  },
  actions: actions,
  strict: debug,
  // plugins: debug ? [createLogger()] : []
})
