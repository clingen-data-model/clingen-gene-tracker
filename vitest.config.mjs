import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        setupFiles: ['./tests/frontend/setup.js'],
        include: ['tests/frontend/**/*.spec.js'],
        clearMocks: true,
        restoreMocks: true,
    },
})
