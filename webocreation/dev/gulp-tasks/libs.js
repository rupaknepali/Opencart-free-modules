var libs = {
  bootstrap: {
    css: 'node_modules/path/to/css.min.css',
    js: 'node_modules/path/to/js.min.js'
  }
};

var js_libs = Object.keys(libs)
  .map(function(key, index) {
    return libs[key].hasOwnProperty('js') ? libs[key].js : '';
  }, [])
  .concat(['libs/js/**/*.js']);

var css_libs = Object.keys(libs)
  .map(function(key, index) {
    return libs[key].hasOwnProperty('css') ? libs[key].css : '';
  }, [])
  .concat(['libs/css/**/*.css']);

module.exports = function(gulp, plugins) {
  return function() {
    gulp
      .src(css_libs)
      .pipe(plugins.concat('libs.css'))
      .pipe(plugins.cssnano())
      .pipe(plugins.rename({ suffix: '.min' }))
      .pipe(gulp.dest('../stylesheet/'));

    gulp
      .src(js_libs)
      .pipe(plugins.uglify())
      .pipe(plugins.concat('libs.min.js'))
      .pipe(gulp.dest('./js/'));
  };
};
