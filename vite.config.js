import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue2'

export default defineConfig({
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm.js'
        }
    },
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: ['node_modules'],
            }
        }
    },
    plugins: [
        laravel({
            input: ['resources/assets/js/app.js', 'resources/assets/sass/app.scss'],
            refresh: true,
        }),
        vue(),
    ],
})
