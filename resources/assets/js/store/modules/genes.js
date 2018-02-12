const baseUrl = '/api/genes'
const state = {
    items: []
}

const getters = {

}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        state.items.push(item)
    }
}

const actions = {
    getAllItems: function ( {commit} ) {
        window.axios.get('/api/genes')
            .then(function (response) {
                commit('setGenes', response.data)
            })
            .catch(function (error) {
                alert(error);
            })
    }
}

export default {
  state,
  getters,
  actions,
  mutations
}
