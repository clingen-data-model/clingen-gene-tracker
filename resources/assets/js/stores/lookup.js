import { defineStore } from 'pinia'

const baseUrl = ''

export const useLookupStore = defineStore('lookup', {
    state: () => ({
        items: [],
    }),

    getters: {
        Items: (state) => state.items,
    },

    actions: {
        setItems(items) {
            this.items = items
        },
        addItem(item) {
            this.items.push(item)
        },

        getAllItems() {
            window.axios
                .get(baseUrl)
                .then((response) => {
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
