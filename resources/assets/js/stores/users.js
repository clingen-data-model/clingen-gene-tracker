import { defineStore } from 'pinia'

const baseUrl = '/api/users'

export const useUsersStore = defineStore('users', {
    state: () => ({
        items: [],
    }),

    getters: {
        Items: (state) => state.items,
        getItemById: (state) => (id) => {
            return state.items.find((item) => item.id == id)
        },
        getCurators: (state) => {
            return state.items.filter((item) => {
                return item.expert_panels.filter((panel) => panel.pivot.is_curator).length > 0
            })
        },
    },

    actions: {
        setItems(items) {
            this.items = items
        },
        addItem(item) {
            this.items.push(item)
        },
        updateItem(item) {
            let itemIdx = this.items.findIndex((i) => i.id == item.id)
            if (itemIdx > -1) {
                this.items.splice(itemIdx, 1, item)
            }
        },

        getAllItems() {
            return window.axios
                .get(baseUrl + '?with=roles,expertPanels')
                .then((response) => {
                    this.setItems(response.data.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
        storeNewItem(data) {
            return window.axios.post(baseUrl, data).then((response) => {
                this.addItem(response.data.data)
                return response
            })
        },
        storeItemUpdates(data) {
            return window.axios.put(baseUrl + '/' + data.id, data).then((response) => {
                this.updateItem(response.data.data)
                return response
            })
        },
    },
})
