function transformPhenotypes(phenotypes) {
    return phenotypes.map(p => p.mim_number);
}

const baseUrl = '/api/topics'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items[id]
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        if (state.items[item.id]) {
            state.items[item.id] = item;   
        }
        state.items[item.id] = item;
    },
    updateItem: function (state, item) {
        item = transformPhenotypes(item.phenotypes);
        state.items[item.id] = item;
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
            })
            .catch(function (error) {
                alert(error);
            })
    },
    storeItemUpdates: function ( {commit}, data ) {
        return window.axios.put(baseUrl+'/'+data.id, data)
            .then(function (response) {
                commit('updateItem', response.data.data);
                return response;
            })
            .catch(function (error) {
                alert(error);
            })
    },
    fetchItem: function ( {commit}, id ) {
        return window.axios.get(baseUrl+'/'+id)
            .then(function (response) {
                let item = response.data.data;
                item.phenotypes = transformPhenotypes(item.phenotypes);
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
