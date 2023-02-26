const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry = {
	personalSettings: path.join(__dirname, 'src', 'personalSettings.js'),
	main: path.join(__dirname, 'src', 'main.js'),
}

module.exports = webpackConfig
