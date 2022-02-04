module.exports = function(gulp, plugins) {
    return function() {
        plugins.browserSync.init({
        proxy: "http://localhost/gulp-oc/",
        notify: true
      });
    };
};
