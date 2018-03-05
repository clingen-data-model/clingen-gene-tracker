const baseUrl = '/api/topics'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items.find(item => item.id == id)
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        state.items.push(item)
    },
    updateItem: function (state, id, item) {
        state.items[id] = item
    },
}

const actions = {
    getAllItems: function ( {commit} ) {
        return window.axios.get(baseUrl)
            .then(function (response) {
                commit('setItems', response.data.data)
            })
            .catch(function (error) {
                alert(error);
            })
    },
    storeNewItem: function ( {commit}, data ) {
        return window.axios.post(baseUrl, data)
            .then(function (response) {
                commit('addItem', response.data.data);
                return response;
            });
    },
    storeItemUpdates: function ( {commit}, data ) {
        return window.axios.put(baseUrl+'/'+data.topic_id, data)
            .then(function (response) {
                commit('updateItem', response.data.data);
                return response;
            });
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
