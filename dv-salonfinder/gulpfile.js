const   gulp = require('gulp'),
        sourcemaps = require('gulp-sourcemaps'),
        babel = require('gulp-babel'),
        urgify = require('gulp-uglify'),
        concat = require('gulp-concat');

gulp.task('javascript', () =>
    gulp.src('src/assets/**/*.js')
        .pipe(sourcemaps.init())
        .pipe(babel({
            presets: ['@babel/preset-env']
        }))
        .pipe(concat('all.js'))
        .pipe(urgify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('dist'))
);