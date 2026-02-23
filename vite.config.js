import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: ['node_modules'],
            }
        }
    },
    resolve: {
        extensions: ['.vue', '.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json'],
    },
    plugins: [
        laravel({
            input: ['resources/assets/js/app.js', 'resources/assets/sass/app.scss'],
            refresh: true,
        }),
        vue(),
    ],
})
