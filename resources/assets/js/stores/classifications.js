import { defineStore } from 'pinia'

const baseUrl = '/api/classifications'

export const useClassificationsStore = defineStore('classifications', {
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
            const data = JSON.parse(localStorage.getItem('classifications'))
            if (data) {
                this.setItems(data)
                return
            }
            window.axios
                .get(baseUrl)
                .then((response) => {
                    localStorage.setItem('classifications', JSON.stringify(response.data))
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
