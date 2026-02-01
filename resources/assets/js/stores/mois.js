import { defineStore } from 'pinia'

const baseUrl = '/api/mois'

export const useMoisStore = defineStore('mois', {
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
            const data = JSON.parse(localStorage.getItem('mois'))
            if (data) {
                this.setItems(data)
            }
            window.axios
                .get(baseUrl)
                .then((response) => {
                    localStorage.setItem('mois', JSON.stringify(response.data))
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
