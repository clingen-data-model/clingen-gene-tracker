import Vue from 'vue'
import moment from 'moment';

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
        return window.axios.delete(baseUrl+'/'+id)
            .then(function (response) {
                commit('removeItem', id);
                return response;
            })
            .catch(function (error) {
                return Promise.reject(error.response);
            })
    },
    linkNewStatus ({commit}, {curation, data}) {
        return window.axios.post(baseUrl+'/'+curation.id+'/statuses', data)
            .then((response) => {
                curation.curation_statuses.push(response.data);
                addOrUpdateItem(commit, curation);
                return response
            })
            .catch(function (error) {
                return Promise.reject(error.response);
            });
    },
    updateStatusDate ({commit}, {curation, pivotId, statusDate}) {
        return window.axios.put(baseUrl + '/' + curation.id + '/statuses/'+pivotId, {'status_date': statusDate})
            .then((response) => {
                const updatedStatusEntry = response.data;
                const curationStatusEntryIdx = curation.curation_statuses.findIndex(cs => cs.pivot.id == updatedStatusEntry.pivot.id);
                curation.curation_statuses[curationStatusEntryIdx] = updatedStatusEntry;
                addOrUpdateItem(commit, curation);
                return response
            })
            .catch(function (error) {
                return Promise.reject(error.response);
            });
    },
    unlinkStatus ({commit}, {curation, pivotId}) {
        return window.axios.delete(baseUrl+'/'+curation.id+'/statuses/'+pivotId)
            .then(response => {
                console.log(response);
                const deletedEntryIdx = curation.curation_statuses.findIndex(cs => cs.pivot.id == pivotId);
                console.log('deletedEntryIdx: '+deletedEntryIdx);
                console.log(curation.curation_statuses);
                curation.curation_statuses.splice(deletedEntryIdx, 1);
                console.log(curation.curation_statuses);
                addOrUpdateItem(commit, curation);
            })
            .catch(errors => {
                return Promise.reject(errors.response)
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
