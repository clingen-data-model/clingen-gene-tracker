const baseUrl = '/api/working-groups'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items.find((item) => item.id == id)
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        let itemIdx = state.items.findIndex(i => i.id == item.id);
        Vue.set(state.items, itemIdx, item)
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
