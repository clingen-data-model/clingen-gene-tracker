import laravel from 'laravel-vite-plugin'
import {defineConfig} from 'vite'
import path from 'path'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'

export default defineConfig({
    plugins: [
        laravel([
            'resources/assets/sass/app.scss',
            'resources/assets/sass/env.scss',
            'resources/assets/sass/_transitions.scss',
            'resources/assets/sass/_variables.scss',
            'resources/assets/js/app.js',
        ]),
        vue({
            template: {
                compilerOptions: {
                    compatConfig: {
                        MODE: 2
                    }
                }, // until vue 3 migration complete
                transformAssetUrls: {
                    // The Vue plugin will re-write asset URLs, when referenced
                    // in Single File Components, to point to the Laravel web
                    // server. Setting this to `null` allows the Laravel plugin
                    // to instead re-write asset URLs to point to the Vite
                    // server instead.
                    base: null,
                    // The Vue plugin will parse absolute URLs and treat them
                    // as absolute paths to files on disk. Setting this to
                    // `false` will leave absolute URLs un-touched so they can
                    // reference assets in the public directory as expected.
                    includeAbsolute: false,
                },
            },
        }),
        vuetify({ autoImport: true }),
    ],
    resolve: {
        extensions: ['.js', '.vue', '.mjs'],
        alias: {
            vue: '@vue/compat/dist/vue.esm-bundler.js', // until we no longer use bootstrapvue
            '@': path.resolve(__dirname, './resources/assets/js'),
        },
    },
});
