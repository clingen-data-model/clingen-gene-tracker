import { defineStore } from 'pinia'

const baseUrl = '/api/working-groups'

export const useWorkingGroupsStore = defineStore('workingGroups', {
    state: () => ({
        items: [],
    }),

    getters: {
        Items: (state) => state.items,
        getItemById: (state) => (id) => {
            return state.items.find((item) => item.id == id)
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

        getAllItems() {
            window.axios
                .get(baseUrl)
                .then((response) => {
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.log(error)
                })
        },
        fetchItem(id) {
            return window.axios
                .get(baseUrl + '/' + id)
                .then((response) => {
                    let item = response.data.data
                    this.updateItem(item)
                    return response
                })
                .catch((error) => {
                    console.log(error)
                })
        },
    },
})
