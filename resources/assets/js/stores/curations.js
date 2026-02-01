import { defineStore } from 'pinia'

const baseUrl = '/api/curations'

export const useCurationsStore = defineStore('curations', {
    state: () => ({
        items: [],
        currentItemIdx: null,
    }),

    getters: {
        Items: (state) => state.items,
        getItemById: (state) => (id) => {
            return state.items.find((item) => item.id == id)
        },
        currentItem: (state) => {
            if (state.currentItemIdx === null) {
                return {}
            }
            return state.items[state.currentItemIdx]
        },
    },

    actions: {
        setItems(items) {
            this.items = items
        },
        addItem(item) {
            const idx = this.items.findIndex((i) => i.id == item.id)
            if (idx > -1) {
                this.items.splice(idx, 1, item)
                return
            }
            this.items.push(item)
        },
        removeItem(id) {
            const itemIdx = this.items.findIndex((i) => i.id == id)
            if (itemIdx > -1) {
                this.items.splice(itemIdx, 1)
            }
        },
        setCurrentItemIdx(curation) {
            const idx = this.items.findIndex((i) => i.id == curation.id)
            this.currentItemIdx = idx
        },

        getAllItems() {
            return window.axios
                .get(baseUrl)
                .then((response) => {
                    this.setItems(response.data.data)
                })
                .catch((error) => {})
        },
        storeNewItem(data) {
            return window.axios.post(baseUrl, data).then((response) => {
                this.addItem(response.data.data)
                return response
            })
        },
        storeItemUpdates(data) {
            return window.axios.put(baseUrl + '/' + data.id, data).then((response) => {
                this.addItem(response.data.data)
                return response
            })
        },
        fetchItem(id) {
            return window.axios
                .get(baseUrl + '/' + id)
                .then((response) => {
                    let item = response.data.data
                    this.addItem(item)
                    this.setCurrentItemIdx(item)
                    return response
                })
                .catch((error) => {
                    return Promise.reject(error.response)
                })
        },
        destroyItem(id) {
            return window.axios
                .delete(baseUrl + '/' + id)
                .then((response) => {
                    this.removeItem(id)
                    return response
                })
                .catch((error) => {
                    return Promise.reject(error.response)
                })
        },
        linkNewStatus({ curation, data }) {
            return window.axios
                .post(baseUrl + '/' + curation.id + '/statuses', data)
                .then((response) => {
                    const status = response.data
                    if (curation.curation_statuses.find((st) => st.pivot.id == status.pivot.id)) {
                        console.log(
                            'weird pivot condition: ',
                            curation.curation_statuses.find((st) => st.pivot.id == status.pivot.id)
                        )
                        return response
                    }
                    curation.curation_statuses.push(status)
                    this.addItem(curation)
                    return response
                })
                .catch((error) => {
                    return Promise.reject(error.response)
                })
        },
        updateStatusDate({ curation, pivotId, statusDate }) {
            return window.axios
                .put(baseUrl + '/' + curation.id + '/statuses/' + pivotId, {
                    status_date: statusDate,
                })
                .then((response) => {
                    const updatedStatusEntry = response.data
                    const curationStatusEntryIdx = curation.curation_statuses.findIndex(
                        (cs) => cs.pivot.id == updatedStatusEntry.pivot.id
                    )
                    curation.curation_statuses[curationStatusEntryIdx] = updatedStatusEntry
                    this.addItem(curation)
                    return response
                })
                .catch((error) => {
                    return Promise.reject(error.response)
                })
        },
        unlinkStatus({ curation, pivotId }) {
            return window.axios
                .delete(baseUrl + '/' + curation.id + '/statuses/' + pivotId)
                .then((response) => {
                    const deletedEntryIdx = curation.curation_statuses.findIndex(
                        (cs) => cs.pivot.id == pivotId
                    )
                    curation.curation_statuses.splice(deletedEntryIdx, 1)
                    this.addItem(curation)
                })
                .catch((errors) => {
                    return Promise.reject(errors.response)
                })
        },
        linkNewClassification({ curation, data }) {
            return window.axios
                .post(baseUrl + '/' + curation.id + '/classifications', data)
                .then((response) => {
                    curation.classifications.push(response.data)
                    this.addItem(curation)
                    return response
                })
        },
        updateClassification({ curation, pivotId, data }) {
            return window.axios
                .put(baseUrl + '/' + curation.id + '/classifications/' + pivotId, data)
                .then((response) => {
                    const updatedClassificationEntry = response.data
                    const curationClassificationEntryIdx = curation.classifications.findIndex(
                        (cs) => cs.pivot.id == updatedClassificationEntry.pivot.id
                    )
                    curation.classifications[curationClassificationEntryIdx] =
                        updatedClassificationEntry
                    this.addItem(curation)
                    return response
                })
                .catch((error) => {
                    return Promise.reject(error.response)
                })
        },
        unlinkClassification({ curation, pivotId }) {
            return window.axios
                .delete(baseUrl + '/' + curation.id + '/classifications/' + pivotId)
                .then((response) => {
                    const deletedEntryIdx = curation.classifications.findIndex(
                        (cs) => cs.pivot.id == pivotId
                    )
                    curation.classifications.splice(deletedEntryIdx, 1)
                    this.addItem(curation)
                })
                .catch((errors) => {
                    return Promise.reject(errors.response)
                })
        },
        updateOwner({ curation, expertPanelId, startDate, notes }) {
            return window.axios
                .post(baseUrl + '/' + curation.id + '/owner', {
                    expert_panel_id: expertPanelId,
                    start_date: startDate,
                    notes: notes,
                })
                .then((response) => {
                    const { curation_id } = response.data
                    this.fetchItem(curation_id)
                    return response
                })
        },
    },
})
