const { CleanWebpackPlugin } = require('clean-webpack-plugin')

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

mix.webpackConfig({
    output: {
        chunkFilename: mix.inProduction() ? 'js/modules/[name].[contenthash].js' : 'js/modules/[name].bundle.js',
        publicPath: '/',
    }
})

mix.webpackConfig({
    plugins: [
        new CleanWebpackPlugin({
            // dry: true,
            cleanOnceBeforeBuildPatterns: ['!**/*', 'js/**/*', 'js/modules/*', 'css/**/*']
        }),
    ]
})

mix.js('resources/assets/js/app.js', 'public/js').vue()
   .sass('resources/assets/sass/app.scss', 'public/css')
   .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}