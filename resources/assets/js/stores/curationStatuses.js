import { defineStore } from 'pinia'

const baseUrl = '/api/curation-statuses'

export const useCurationStatusesStore = defineStore('curationStatuses', {
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
            const data = JSON.parse(localStorage.getItem('curation_statuses'))
            if (data) {
                this.setItems(data)
                return
            }
            window.axios
                .get(baseUrl)
                .then((response) => {
                    localStorage.setItem('curation_statuses', JSON.stringify(response.data))
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
