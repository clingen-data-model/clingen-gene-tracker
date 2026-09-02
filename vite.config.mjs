import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/assets/sass/app.scss',
                'resources/assets/js/app.js',
            ],
        }),
        vue(),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
    build: {
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: 'ui-vendor',
                            test: /node_modules[\\/](?:bootstrap(?:-vue-next)?|@floating-ui[\\/]|@vueuse[\\/]|reka-ui)[\\/]/,
                            priority: 3,
                        },
                        {
                            name: 'vue-vendor',
                            test: /node_modules[\\/](?:@vue[\\/]|vue(?:-router|x)?[\\/])/,
                            priority: 2,
                        },
                        {
                            name: 'vendor',
                            test: /node_modules[\\/]/,
                            priority: 1,
                        },
                    ],
                },
            },
        },
    },
})
