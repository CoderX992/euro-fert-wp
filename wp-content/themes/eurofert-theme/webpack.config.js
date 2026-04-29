const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
module.exports = {
  ...defaultConfig,
  plugins: [
    ...defaultConfig.plugins,
    new RemoveEmptyScriptsPlugin()
  ]
};