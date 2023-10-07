let mix = require('laravel-mix')

require('./nova.mix')

mix
  .setPublicPath('dist')
  .js('resources/js/index.js', 'js')
  .vue({ version: 3 })
  .nova('nevadskiy/collection-field')
