import { defineStore } from 'pinia'
import queryStringFromParams from '../http/query_string_from_params'

const baseUrl = '/api/expert-panels'

export const usePanelsStore = defineStore('panels', {
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
        setItem(item) {
            const idx = this.items.findIndex((panel) => panel.id == item.id)
            if (idx > -1) {
                this.items.splice(idx, 1, item)
                return
            }
            this.addItem(item)
        },

        getAllItems(params) {
            let url = baseUrl + queryStringFromParams(params)
            window.axios
                .get(url)
                .then((response) => {
                    this.setItems(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
        getItem({ id, params }) {
            const url = `${baseUrl}/${id}/${queryStringFromParams(params)}`
            window.axios
                .get(url)
                .then((response) => {
                    this.setItem(response.data)
                })
                .catch((error) => {
                    console.error(error)
                })
        },
    },
})
