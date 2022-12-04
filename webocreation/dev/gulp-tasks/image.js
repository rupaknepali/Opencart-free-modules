module.exports = function(gulp, plugins) {
  return function() {
    gulp
      .src('image/**/*')
      .pipe(
        plugins.cache(
          plugins.imagemin({
            interlaced: true,
            progressive: true,
            svgoPlugins: [
              {
                removeViewBox: false
              }
            ],
            use: [plugins.pngquant]
          })
        )
      )
      .pipe(gulp.dest('../image/'));
  };
};
