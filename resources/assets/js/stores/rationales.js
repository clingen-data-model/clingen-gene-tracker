import { defineStore } from 'pinia'

const baseUrl = '/api/rationales'

export const useRationalesStore = defineStore('rationales', {
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
            localStorage.removeItem('rationales')
            const data = JSON.parse(localStorage.getItem('rationales'))
            if (data) {
                this.setItems(data)
                return
            }
            window.axios
                .get(baseUrl)
                .then((response) => {
                    localStorage.setItem('rationales', JSON.stringify(response.data))
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
