import Vue from 'vue'

function transformPhenotypes(phenotypes) {
    const out = phenotypes.map(p => {
        if (p.mim_number) {
            return p.mim_number
        }
        return p
    });
    return out;
}

const baseUrl = '/api/curations'
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
        // item.phenotypes = transformPhenotypes(item.phenotypes);
        console.log(item)
        Vue.set(state.items, item.id-1, item)
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
        return window.axios.put(baseUrl+'/'+data.id, data)
            .then(function (response) {
                commit('addItem', response.data.data);
                return response;
            });
    },
    fetchItem ( {commit}, id ) {
        return window.axios.get(baseUrl+'/'+id)
            .then(function (response) {
                let item = response.data.data;
                // item.phenotypes = transformPhenotypes(item.phenotypes);
                console.log(item)
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
