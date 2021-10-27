var gulp = require("gulp");
var zip = require("gulp-zip");
var clean = require("gulp-clean");
let cleanCSS = require("gulp-clean-css");
var uglify = require('gulp-uglify-es').default;
var pump = require("pump");



gulp.task("copy_module_weather", function () {
  return gulp.src("./modules/mod_sp_weather/**/*.*").pipe(gulp.dest("build/mod_sp_weather"));
});


gulp.task("copy_lang_mod", function () {
  return gulp.src(["./language/en-GB/en-GB.mod_sp_weather.ini"])
  .pipe(gulp.dest("build/mod_sp_weather/"));
});


gulp.task(
  "copy",
  gulp.series(
    "copy_module_weather",
    "copy_lang_mod",
  ),
);

gulp.task("minify_mod_css", function () {
  return gulp
    .src("build/mod_sp_weather/assets/css/*.css")
    .pipe(cleanCSS())
    .pipe(gulp.dest("build/mod_sp_weather/assets/css/"));
});


gulp.task(
  "minify",
  gulp.series(
    "minify_mod_css"
  ),
);

gulp.task("zip_it", function () {
  return gulp.src("./build/**/*.*").pipe(zip("mod_sp_weather_v4.0.1.zip")).pipe(gulp.dest("./"));
});

gulp.task("clean_build", function () {
  return gulp.src("./build", { read: false, allowEmpty: true }).pipe(clean());
});

gulp.task("clean_zip", function () {
  return gulp.src("./mod_sp_weather_v4.0.1.zip", { read: false, allowEmpty: true }).pipe(clean());
});

gulp.task(
  "default",
  gulp.series("clean_zip", "clean_build", "copy", "minify", "zip_it", function () {
    return gulp.src("./build/**/*.*").pipe(zip("mod_sp_weather_v4.0.1.zip")).pipe(gulp.dest("./"));
  }),
);
