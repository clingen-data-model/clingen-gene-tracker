import queryStringFromParams from '../../http/query_string_from_params'

const baseUrl = '/api/expert-panels'
const state = {
    items: []
}

const getters = {
    Items: function (state) {
        return state.items;
    }
}

const mutations = {
    setItems: function (state, items) {
        state.items = items
    },
    addItem: function (state, item) {
        state.items.push(item)
    },
    setItem: function (state, item) {
        const idx = state.items.findIndex(panel => panel.id == item.id);
        if (idx > -1) {
            state.items.splice(idx, 1, item)
            return;
        }
        this.addItem(state, item);
    }
}

const actions = {
    getAllItems: function ( {commit}, params ) {
        let url = baseUrl+queryStringFromParams(params);
        window.axios.get(url)
            .then(function (response) {
                commit('setItems', response.data)
            })
            .catch(function (error) {
                console.error(error);
            })
    },
    getItem ({commit}, {id, params}) {
        console.log(id);
        const url = `${baseUrl}/${id}/${queryStringFromParams(params)}`;
        window.axios.get(url)
            .then(function (response) {
                commit('setItem', response.data);
            })
            .catch(function (error) {
                console.error(error)
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
