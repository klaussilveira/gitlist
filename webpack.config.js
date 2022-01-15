const encore = require('@symfony/webpack-encore');
const webpack = require('webpack');
const { readdirSync, statSync } = require('fs');
const { join } = require('path');

const getDirectories = path => readdirSync(path).filter(
  folder => statSync(join(path, folder)).isDirectory()
);

const themes = getDirectories('./assets/themes/');
const viewers = getDirectories('./assets/viewers/');

encore
  .setOutputPath('public/assets/')
  .setPublicPath('/assets')
  .cleanupOutputBeforeBuild()
  .disableSingleRuntimeChunk()
  .enableSourceMaps(!encore.isProduction())
  .enableSassLoader()
  .enablePostCssLoader()
;

for (const theme of themes) {
  encore.addStyleEntry(theme + '/css/main', './assets/themes/' + theme + '/assets/scss/main.scss');
  encore.addEntry(theme + '/js/main', './assets/themes/' + theme + '/assets/js/main.js');
}

for (const viewer of viewers) {
  encore.addStyleEntry(viewer + '/css/main', './assets/viewers/' + viewer + '/main.scss');
  encore.addEntry(viewer + '/js/main', './assets/viewers/' + viewer + '/main.js');
}

module.exports = encore.getWebpackConfig();
