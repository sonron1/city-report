const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .addEntry('signalement-map', './assets/js/signalement-map.js')
    .addEntry('carte-index', './assets/js/carte-index.js')
    .addEntry('admin', './assets/js/admin.js') // Script pour l'administration
    .addStyleEntry('styles', './assets/styles/app.scss')
    .addStyleEntry('admin-styles', './assets/styles/admin.scss') // Styles pour l'administration
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader()
    // Copier les images de Leaflet
    .copyFiles({
        from: './node_modules/leaflet/dist/images',
        to: 'images/[path][name].[ext]'
    })
    // Copier les images pour l'administration
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]',
        pattern: /\.(png|jpg|jpeg|svg|gif)$/
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
;

module.exports = Encore.getWebpackConfig();