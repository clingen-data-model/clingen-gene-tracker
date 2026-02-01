import { defineStore } from 'pinia'

export const useMessagesStore = defineStore('messages', {
    state: () => ({
        info: [],
        errors: [],
    }),

    getters: {
        all: (state) => state,
    },

    actions: {
        addInfo(message) {
            if (this.info.indexOf(message) === -1) {
                this.info.push(message)
            }
        },
        removeInfo(idx) {
            this.info.splice(idx, 1)
        },
        addError(message) {
            this.errors.push(message)
        },
        removeError(idx) {
            this.errors.splice(idx, 1)
        },
    },
})
