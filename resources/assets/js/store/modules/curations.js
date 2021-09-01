import Vue from 'vue'
import moment from 'moment';

const baseUrl = '/api/curations'
const state = {
    items: [],
    currentItemIdx: null
}

const getters = {
    Items: function(state) {
        return state.items;
    },
    getItemById: (state) => (id) => {
        return state.items.find((item) => item.id == id)
    },
    currentItem: state => {
        if (state.currentItemIdx === null) {
            return {};
        }

        return state.items[state.currentItemIdx]
    },
}

const mutations = {
    setItems: function(state, items) {
        state.items = items
    },
    addItem: function(state, item) {
        const idx = state.items.findIndex(i => i.id == item.id);
        if (idx > -1) {
            state.items.splice(idx, 1, item)
            return;
        }

        state.items.push(item)
    },
    updateItem: function(state, item) {
        console.log('updateitem')
    },
    removeItem: function(state, id) {
        const itemIdx = state.items.findIndex(i => i.id == id);

        Vue.delete(state.items, itemIdx);
    },
    setCurrentItemIdx(state, curation) {
        const idx = state.items.findIndex(i => i.id == curation.id);
        state.currentItemIdx = idx;
    },
}

const actions = {
    getAllItems: function({ commit }) {
        return window.axios.get(baseUrl)
            .then(function(response) {
                commit('setItems', response.data.data)
            })
            .catch(function(error) {})
    },
    storeNewItem: function({ commit }, data) {
        return window.axios.post(baseUrl, data)
            .then(function(response) {
                commit('addItem', response.data.data);
                return response;
            });
    },
    storeItemUpdates: function({ commit }, data) {
        return window.axios.put(baseUrl + '/' + data.id, data)
            .then(function(response) {
                commit('addItem', response.data.data);
                return response;
            });
    },
    fetchItem({ commit }, id) {
        return window.axios.get(baseUrl + '/' + id)
            .then((response) => {
                let item = response.data.data;
                commit('addItem', item);
                commit('setCurrentItemIdx', item);
                return response;
            })
            .catch((error) => {
                return Promise.reject(error.response);
            })
    },
    destroyItem({ commit }, id) {
        return window.axios.delete(baseUrl + '/' + id)
            .then(function(response) {
                commit('removeItem', id);
                return response;
            })
            .catch(function(error) {
                return Promise.reject(error.response);
            })
    },
    linkNewStatus({ commit }, { curation, data }) {
        return window.axios.post(baseUrl + '/' + curation.id + '/statuses', data)
            .then((response) => {
                const status = response.data;
                if (curation.curation_statuses.find(st => st.pivot.id == status.pivot.id)) {
                    return response;
                }
                curation.curation_statuses.push(status);
                commit('addItem', curation);
                return response
            })
            .catch(function(error) {
                return Promise.reject(error.response);
            });
    },
    updateStatusDate({ commit }, { curation, pivotId, statusDate }) {
        return window.axios.put(baseUrl + '/' + curation.id + '/statuses/' + pivotId, { 'status_date': statusDate })
            .then((response) => {
                const updatedStatusEntry = response.data;
                const curationStatusEntryIdx = curation.curation_statuses.findIndex(cs => cs.pivot.id == updatedStatusEntry.pivot.id);
                curation.curation_statuses[curationStatusEntryIdx] = updatedStatusEntry;
                commit('addItem', curation);
                return response
            })
            .catch(function(error) {
                return Promise.reject(error.response);
            });
    },
    unlinkStatus({ commit }, { curation, pivotId }) {
        return window.axios.delete(baseUrl + '/' + curation.id + '/statuses/' + pivotId)
            .then(response => {
                const deletedEntryIdx = curation.curation_statuses.findIndex(cs => cs.pivot.id == pivotId);
                curation.curation_statuses.splice(deletedEntryIdx, 1);
                commit('addItem', curation);
            })
            .catch(errors => {
                return Promise.reject(errors.response)
            });
    },
    linkNewClassification({ commit }, { curation, data }) {
        return window.axios.post(baseUrl + '/' + curation.id + '/classifications', data)
            .then((response) => {
                curation
                    .classifications
                    .push(response.data);
                commit('addItem', curation);
                return response
            })
    },
    updateClassification({ commit }, { curation, pivotId, data }) {
        return window.axios.put(baseUrl + '/' + curation.id + '/classifications/' + pivotId, data)
            .then((response) => {
                const updatedClassificationEntry = response.data;
                const curationClassificationEntryIdx = curation.classifications.findIndex(cs => cs.pivot.id == updatedClassificationEntry.pivot.id);
                curation.classifications[curationClassificationEntryIdx] = updatedClassificationEntry;
                commit('addItem', curation);
                return response
            })
            .catch(function(error) {
                return Promise.reject(error.response);
            });
    },
    unlinkClassification({ commit }, { curation, pivotId }) {
        return window.axios.delete(baseUrl + '/' + curation.id + '/classifications/' + pivotId)
            .then(response => {
                const deletedEntryIdx = curation.classifications.findIndex(cs => cs.pivot.id == pivotId);
                curation.classifications.splice(deletedEntryIdx, 1);
                commit('addItem', curation);
            })
            .catch(errors => {
                return Promise.reject(errors.response)
            });
    },
    updateOwner({ commit }, { curation, expertPanelId, startDate, notes }) {
        return window.axios.post(baseUrl + '/' + curation.id + '/owner', {
                expert_panel_id: expertPanelId,
                start_date: startDate,
                notes: notes
            })
            .then(response => {
                const { curation_id, expert_panels } = response.data
                curation.expert_panels = expert_panels;
                curation.expert_panel_id = expertPanelId;
                curation.expert_panel = expert_panels.find(ep => ep.id == expertPanelId);
                console.log(curation.expert_panels);
                commit('addItem', curation);
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