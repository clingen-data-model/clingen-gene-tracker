import Vue from 'vue'

const baseUrl = '/api/curations'
const state = {
    items: []
}

const addOrUpdateItem = function (commit, item) {
    let itemIdx = state.items.findIndex(i => i.id == item.id);
    if (itemIdx > -1) {
        commit('updateItem', item);
        return
    }
    commit('addItem', item);
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items.find( (item) => item.id == id)
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
        if (itemIdx > -1) {
            Vue.set(state.items, itemIdx, item)
            return
        }
        console.error('curation is not found in store.  you should commit(\'addItem\', item) instead');
    },
    removeItem: function (state, id) {
        const itemIdx = state.items.findIndex(i => i.id == id);
        
        Vue.delete(state.items, itemIdx);
    }
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
        return window.axios.put(baseUrl+'/'+data.id, data)
            .then(function (response) {
                addOrUpdateItem(commit, response.data.data);
                return response;
            });
    },
    fetchItem ( {commit}, id ) {
        return window.axios.get(baseUrl+'/'+id)
            .then((response) => {
                let item = response.data.data;
                addOrUpdateItem(commit, item);
                return response;
            })
            .catch((error) => {
                return Promise.reject(error.response);
            })
    },
    destroyItem ( {commit}, id) {
        console.log(id);
        return window.axios.delete(baseUrl+'/'+id)
            .then(function (response) {
                commit('removeItem', id);
                return response;
            })
            .catch(function (error) {
                return Promise.reject(error.response);
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
