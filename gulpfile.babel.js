import autoprefixer   from 'autoprefixer'
import gulp           from 'gulp'
import livereload     from 'gulp-livereload'
import postcss        from 'gulp-postcss'
// import readmeToMD     from 'gulp-readme-to-markdown'
import rename         from 'gulp-rename'
import sass           from 'gulp-sass'
import sourcemaps     from 'gulp-sourcemaps'
import gutil          from 'gulp-util'
import merge          from 'merge-stream'
import webpack        from 'webpack'
import webpackStream  from 'webpack-stream'

const PRODUCTION = process.argv.indexOf('--production') > -1

// gulp.task('readme', readme)
gulp.task('build', /*['readme'], */build)
gulp.task('watch', /*['readme'], */watch)
gulp.task('default', ['watch'])

function build() {
  return merge(
    styles(),
    scripts(getWebpackBaseConfig())
  )
}

function watch() {
  livereload.listen()

  // gulp.watch('skoorin/readme.txt', readme)
  gulp.watch('styles/*.scss', styles)
  gulp.watch(['skoorin/scripts/*.js', 'skoorin/styles/**/*.css', 'skoorin/**/*.php'], (evt) =>
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
  return gulp.src('styles/[^_]*.scss')
    .pipe(rename(path => path.basename = `skoorin-${path.basename}`))
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(postcss([autoprefixer(/* project-wide options are in browserslist file in project root */)]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('skoorin/styles'))
}

function scripts(config) {
  return webpackStream(config)
    .on('error', function (err) {
      console.error(err.stack || err)
      this.emit('end')
    })
    .pipe(gulp.dest('skoorin/scripts'))
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
      entry: './scripts/results.js',
      output: {
        filename: 'skoorin-results.js'
      }
    }, {
      ...config,
      entry: './scripts/settings.js',
      output: {
        filename: 'skoorin-settings.js'
      }
    }]
  }
}

/*function readme() {
  return gulp
    .src('skoorin/readme.txt')
    .pipe(readmeToMD({
      screenshot_url: 'screenshots/{screenshot}.{ext}',
      screenshot_ext: 'gif',
      extract: {}
    }))
    .pipe(gulp.dest('.'))

  /* add screenshots to README.md manually:
  ![](skoorin/screenshot-1.gif)<br>
  1. Results table with filter

  ![](skoorin/screenshot-2.gif)<br>
  2. Results filter options
  *
}*/