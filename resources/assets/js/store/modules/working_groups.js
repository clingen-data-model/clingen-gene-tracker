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
        state.items.push(item)
    },
    updateItem: function (state, item) {
        console.log('updateItem');
        let itemIdx = state.items.findIndex(i => i.id == item.id);
        if (itemIdx > -1) {
            Vue.set(state.items, itemIdx, item)
            return
        }
        commit('addItem', item);
    },
    removeItem: function (state, id) {
        const itemIdx = state.items.findIndex(i => i.id == id);

        Vue.delete(state.items, itemIdx);
    }
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
                let item = response.data.data;
                commit('updateItem', item);
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
