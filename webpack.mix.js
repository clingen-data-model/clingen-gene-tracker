let mix = require('laravel-mix');

mix.options({
    hmrOptions: {
        host: "localhost",
        port: '8081'
    },
});

if (mix.dev)
mix.webpackConfig({
    // mode: "development",
    devtool: "inline-source-map",
    devServer: {
        disableHostCheck: true,
        headers: {
            'Access-Control-Allow-Origin': '*'
        },
        host: "localhost",
        port: '8081'
    },
    plugins: [
        // new BundleAnalyzerPlugin()
        // new IgnorePlugin(/^\.\/locale$/, /moment$/)
    ],
    resolve: {
        alias: {
            moment: 'moment/src/moment',
        }
    }
});

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css')
   .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}