'use strict';

/*
 * Runalyze JS bundle build (phase 1 of the frontend build modernization).
 *
 * Replaces the old gulp concat+terser pipeline. The file order below mirrors
 * the former resources.json exactly. esbuild then minifies the concatenated
 * source in sloppy mode, so top-level `var` declarations and implicit
 * global assignments behave exactly as they did in the old bundle.
 *
 * The npm: prefix resolves a package from node_modules via its entry point
 * (for d3 packages that is the UMD dist build, which assigns to window.d3).
 */

const fs = require('fs');
const path = require('path');
const esbuild = require('esbuild');

const OUT_FILE = path.resolve('public/assets/js/scripts.min.js');

const jsFiles = [
  'assets/js/polyfill.js',
  'public/vendor/jquery/dist/jquery.js',
  'lib/jquery.metadata.js',
  'public/vendor/tablesorter/js/jquery.tablesorter.js',
  'public/vendor/tablesorter/addons/pager/jquery.tablesorter.pager.js',
  'lib/bootstrap-tooltip.js',
  'lib/fineuploader-3.5.0.min.js',
  'lib/jquery.datepicker.js',
  'lib/jquery.chosen.min.js',
  'lib/runalyze.lib.js',
  'lib/runalyze.lib.common.js',
  'lib/runalyze.lib.formatter.js',
  'lib/runalyze.lib.plot.js',
  'lib/runalyze.lib.plot.options.js',
  'lib/runalyze.lib.plot.saver.js',
  'lib/runalyze.lib.plot.events.js',
  'lib/runalyze.lib.tablesorter.js',
  'lib/runalyze.lib.options.js',
  'lib/runalyze.lib.config.js',
  'lib/runalyze.lib.overlay.js',
  'lib/runalyze.lib.panels.js',
  'lib/runalyze.lib.databrowser.js',
  'lib/runalyze.lib.statistics.js',
  'lib/runalyze.lib.training.js',
  'lib/runalyze.lib.activity.form.js',
  'lib/runalyze.lib.feature.js',
  'lib/runalyze.lib.notifications.js',
  'lib/runalyze.lib.sport.js',
  'lib/flot-0.8.3/base64.js',
  'lib/flot-0.8.3/jquery.flot.min.js',
  'lib/flot-0.8.3/jquery.flot.resize.min.js',
  'lib/flot-0.8.3/jquery.flot.selection.js',
  'lib/flot-0.8.3/jquery.flot.crosshair.js',
  'lib/flot-0.8.3/jquery.flot.navigate.min.js',
  'lib/flot-0.8.3/jquery.flot.hiddengraphs.js',
  'lib/flot-0.8.3/jquery.flot.stack.js',
  'lib/flot-0.8.3/jquery.flot.textLegend.js',
  'lib/flot-0.8.3/jquery.flot.orderBars.js',
  'lib/flot-0.8.3/jquery.flot.canvas.js',
  'lib/flot-0.8.3/jquery.flot.time.min.js',
  'lib/flot-0.8.3/jquery.flot.curvedLines.js',
  'lib/runalyze.lib.plot.flot.js',
  'lib/geohash.js',
  'public/vendor/leaflet/dist/leaflet.js',
  'lib/leaflet/runalyze.leaflet.js',
  'lib/leaflet/runalyze.leaflet.layers.js',
  'lib/leaflet/runalyze.leaflet.routes.js',
  'public/vendor/d3/d3.js',
  'public/vendor/d3-tip/index.js',
  'npm:d3-scale-chromatic',
  'assets/js/d3js-runalyzeplot.js',
  'assets/js/d3js-boxplot.js',
  'assets/js/climb-score.js',
  'assets/js/views/climb-score-view.js',
  'assets/js/views/race-performance-chart-view.js',
  'assets/js/vendor/d3-annotation.js',
  'public/vendor/fontIconPicker/jquery.fonticonpicker.js',
  'public/vendor/moment/moment.js',
  'public/vendor/moment-timezone/builds/moment-timezone-with-data-1970-2030.min.js',
  'public/vendor/moment-duration-format/lib/moment-duration-format.js',
  'public/vendor/ifvisible.js/src/ifvisible.js',
  'public/vendor/jquery-timeago/jquery.timeago.js',
  'public/vendor/regression-js/build/regression.min.js'
];

function resolveFile(file) {
  if (file.startsWith('npm:')) {
    const spec = file.slice(4);
    const slash = spec.indexOf('/');
    const pkg = slash === -1 ? spec : spec.slice(0, slash);
    const sub = slash === -1 ? null : spec.slice(slash + 1);
    try {
      // Package entry point ("require"/"main" target): for d3 packages
      // this is the UMD dist build. Subpaths like ./dist/... may be
      // blocked by the package "exports" field (d3 v3+), hence this path.
      return require.resolve(spec);
    } catch (e) {
      // Fallback: resolve the package root via its package.json (always
      // resolvable) and append the subpath manually.
      const root = path.dirname(require.resolve(pkg + '/package.json'));
      return sub ? path.join(root, sub) : root;
    }
  }
  return path.resolve(file);
}

try {
  const chunks = jsFiles.map((file) => {
    const resolved = resolveFile(file);
    if (!fs.existsSync(resolved)) {
      throw new Error('Missing file: ' + file + ' (resolved: ' + resolved + '). Did you forget "npm install" or "git add public/vendor"?');
    }
    return '// ---- ' + file + ' ----\n' + fs.readFileSync(resolved, 'utf8');
  });

  const concatenated = chunks.join('\n;\n');

  const result = esbuild.transformSync(concatenated, {
    loader: 'js',
    minify: true,
    target: 'es2017',
    sourcemap: 'external',
    sourcefile: 'scripts.min.js'
  });

  fs.mkdirSync(path.dirname(OUT_FILE), { recursive: true });
  fs.writeFileSync(OUT_FILE, result.code + '\n//# sourceMappingURL=scripts.min.js.map\n');
  fs.writeFileSync(OUT_FILE + '.map', result.map);
  console.log('built ' + OUT_FILE + ' (' + jsFiles.length + ' files)');
} catch (e) {
  console.error(e && e.message ? e.message : e);
  process.exit(1);
}
