module.exports = function(gulp, plugins) {
  return function() {
    plugins.browserSync.init({
      proxy: 'http://opencart.loc/',
      notify: true
    });
  };
};
