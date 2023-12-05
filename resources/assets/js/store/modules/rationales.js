const baseUrl = '/api/rationales'
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
    }
}

const actions = {
    getAllItems: function ( {commit} ) {
        localStorage.removeItem('rationales');
        const data = JSON.parse(localStorage.getItem('rationales'));
        if (data) {
            commit('setItems', data)
            return;
        }
        window.axios.get(baseUrl)
            .then(function (response) {
                localStorage.setItem('rationales', JSON.stringify(response.data));
                commit('setItems', response.data)
            })
            .catch(function (error) {
                console.error(error);
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
