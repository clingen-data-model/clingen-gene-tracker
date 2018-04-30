const baseUrl = '/api/working-groups'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items[id-1]
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        Vue.set(state.items, item.id-1, item)
    },
}

const actions = {
    getAllItems: function ( {commit} ) {
        window.axios.get(baseUrl)
            .then(function (response) {
                commit('setItems', response.data)
            })
            .catch(function (error) {
                alert(error);
            })
    },
    fetchItem ( {commit}, id ) {
        return window.axios.get(baseUrl+'/'+id)
            .then(function (response) {
                let item = response.data;
                commit('addItem', item);
                return response;
            })
            .catch(function (error) {
                alert(error);
            })
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
