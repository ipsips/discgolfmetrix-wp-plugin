import gulp                     from 'gulp'
import livereload               from 'gulp-livereload'
import gutil                    from 'gulp-util'
import webpack                  from 'webpack'
import webpackStream            from 'webpack-stream'
import ExtractTextPlugin        from 'extract-text-webpack-plugin'
import OptimizeCssAssetsPlugin  from 'optimize-css-assets-webpack-plugin'

const PRODUCTION = process.argv.indexOf('--production') > -1

gulp.task('build', build)
gulp.task('watch', watch)
gulp.task('default', ['watch'])

function build() {
  return compile(getWebpackBaseConfig())
}

function watch() {
  livereload.listen()

  gulp.watch(['scripts/**/*.js', 'styles/**/*.css', '**/*.php'], (evt) =>
    livereload.changed(evt.path)
  )

  return compile({
    ...getWebpackBaseConfig(),
    watch: true
  })
}

function compile(config) {
  return webpackStream(config)
    .on('error', error)
    .pipe(gulp.dest('scripts'))
}

function getWebpackBaseConfig() {
  const cssLoader = `css?sourceMap&modules&importLoaders=2&localIdentName=[name]_[local]_[hash:base64:6]!postcss!sass?sourceMap`

  return {
    entry: './scripts/src/index.js',
    target: 'web',
    output: {
      filename: 'skoorin.js'
    },
    plugins: [
      new webpack.DefinePlugin({ PRODUCTION }),
      new webpack.optimize.UglifyJsPlugin(),
      new ExtractTextPlugin('../styles/skoorin.css'),
      new OptimizeCssAssetsPlugin()
    ],
    module: {
      loaders: [{
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      }, {
        test: /\.scss$/,
        loader: PRODUCTION
          ? ExtractTextPlugin.extract('style', cssLoader)
          : `style!${cssLoader}`
      }, {
        test: /\.(jpg|jpeg|png|woff|woff2|eot|ttf|svg)/,
        loader: 'url-loader?limit=100000'
      }]
    },
    devtool: 'source-map',
    postcss: function () {
      return [
        require('autoprefixer')(/* project-wide options are in browserslist file in project root */)
      ]
    }
  }
}

function error(err) {
  console.error(err.stack || err)
  this.emit('end')
}