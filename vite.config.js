import laravel from 'laravel-vite-plugin'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import { defineConfig } from 'vite'
import path from 'path'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath } from 'node:url'

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
        transformAssetUrls,
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
    }),
    quasar({
      autoImportComponentCase: 'kebab',
      sassVariables: new URL('./resources/assets/styles/quasar-variables.sass', import.meta.url)
    }),
    AutoImport({
      include: [/\.[jt]sx?$/, /\.vue$/,],
      imports: [
        'vue',
        'vue-router',
        '@vueuse/core',
        // 'pinia', // maybe later...
        'vuex',
      ],
      dts: 'resources/assets/js/auto-imports.d.ts',
      // vueTemplate: true, // maybe later...
    }),
    tailwindcss({
      darkMode: false, // so much inconsistant css right now, this looks awful...
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/assets/js'),
    },
  }
});
