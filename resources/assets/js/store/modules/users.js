const baseUrl = '/api/users'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items.find(item => item.id == id)
    },
    getCurators: (state) => {
        return state.items.filter(item => { 
            return item.expert_panels.filter(panel => panel.pivot.is_curator).length > 0
        })
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        state.items.push(item)
    },
    updateItem: function (state, item) {
        let itemIdx = state.items.findIndex(i => i.id == item.id);
        Vue.set(state.items, itemIdx, item)
    },
}

const actions = {
    getAllItems: function ( {commit} ) {
        return window.axios.get(baseUrl+'?with=roles,expertPanels')
            .then(function (response) {
                commit('setItems', response.data.data)
            })
            .catch(function (error) {
                console.error(error);
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
        return window.axios.put(baseUrl+'/'+data.id, data)
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
