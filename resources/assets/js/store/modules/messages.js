const state = {
    info: [],
    errors: []
}

const getters = {
    info: function (state) {
        return state.info;
    },
    errors: function (state) {
        return state.errors;
    },
    all: function (state) {
        return state;
    }
}

const mutations = {
    addInfo: function (state, message) {
        state.info.push(message)
    },
    removeInfo: function (state, idx) {
        state.info.splice(idx,1);
    },
    addError: function (state, message) {
        state.errors.push(message)
    },
    removeError: function (state, idx) {
        state.errors.splice(idx,1);
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations
}
