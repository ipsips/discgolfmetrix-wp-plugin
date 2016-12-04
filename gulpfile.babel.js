import autoprefixer   from 'autoprefixer'
import gulp           from 'gulp'
import livereload     from 'gulp-livereload'
import postcss        from 'gulp-postcss'
import sass           from 'gulp-sass'
import sourcemaps     from 'gulp-sourcemaps'
import gutil          from 'gulp-util'
import merge          from 'merge-stream'
import webpack        from 'webpack'
import webpackStream  from 'webpack-stream'

const PRODUCTION = process.argv.indexOf('--production') > -1

gulp.task('build', build)
gulp.task('watch', watch)
gulp.task('default', ['watch'])

function build() {
  return merge(
    styles(),
    scripts(getWebpackBaseConfig())
  )
}

function watch() {
  livereload.listen()

  gulp.watch('styles/*.scss', styles);
  gulp.watch(['scripts/*.js', 'styles/**/*.css', '**/*.php'], (evt) =>
    livereload.changed(evt.path)
  )

  return merge(
    styles(),
    scripts({
      ...getWebpackBaseConfig(),
      watch: true
    })
  )
}

function styles() {
  return gulp.src('styles/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(postcss([autoprefixer(/* project-wide options are in browserslist file in project root */)]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('styles'))
}

function scripts(config) {
  return webpackStream(config)
    .on('error', function (err) {
      console.error(err.stack || err)
      this.emit('end')
    })
    .pipe(gulp.dest('scripts'))
}

function getWebpackBaseConfig() {
  const config = {
    target: 'web',
    plugins: [
      new webpack.DefinePlugin({
        PRODUCTION,
        /**
         * Keeping it "production" for Redux
         * @see https://github.com/reactjs/redux/issues/1029
         */
        'process.env.NODE_ENV': JSON.stringify('production')
      }),
      new webpack.optimize.UglifyJsPlugin()
    ],
    module: {
      loaders: [{
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      }, {
        test: /\.(jpg|jpeg|png|woff|woff2|eot|ttf|svg)/,
        loader: 'url-loader?limit=100000'
      }]
    },
    devtool: 'source-map'
  }

  return {
    config: [{
      ...config,
      entry: './scripts/src/results.js',
      output: {
        filename: 'skoorin-results.js'
      }
    }, {
      ...config,
      entry: './scripts/src/settings.js',
      output: {
        filename: 'skoorin-settings.js'
      }
    }]
  }
}