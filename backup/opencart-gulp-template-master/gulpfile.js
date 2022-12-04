var autoprefixer = require('gulp-autoprefixer');
var browserify = require('gulp-browserify');
var browserSync = require('browser-sync');
var buffer = require('vinyl-buffer');
var cache = require('gulp-cache');
var concat = require('gulp-concat');
var cssmin = require('gulp-cssmin');
var cssnano = require('gulp-cssnano');
var del = require('del');
var gulp = require('gulp');
var imagemin = require('gulp-imagemin');
var jpegtran = require('imagemin-jpegtran');
var log = require('gulplog');
var notify = require("gulp-notify");
var plumber = require('gulp-plumber');
var pngquant = require('imagemin-pngquant')();
var reload = browserSync.reload;
var sass = require('gulp-sass');
var source = require('vinyl-source-stream');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var uncss = require('gulp-uncss');

var plugins = require('gulp-load-plugins')({pattern: '*'});

function getTask(task) {
    return require('./gulp-tasks/' + task)(gulp, plugins);
}

gulp.task('browser-sync', getTask('browser-sync'));
gulp.task('scripts', getTask('scripts'));
gulp.task('sass', getTask('sass'));
gulp.task('image', getTask('image'));
gulp.task('libs', getTask('libs'));

gulp.task('watch', ['browser-sync', 'sass', 'scripts', 'image', 'libs'], function() {
	gulp.watch('dev/scss/**/*.scss', ['sass']);
	gulp.watch('dev/js/**/*.js', ['scripts']);
	gulp.watch('template/**/*.twig', reload);
});

gulp.task('default', ['watch']);
