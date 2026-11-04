'use strict';

/*
 * Port of the former gulp "translate" task.
 *
 * Compiles all gettext catalogs with msgfmt. The odd copy of each .po file
 * to the gettext root is preserved from the original gulpfile.
 *
 * Requires gettext (msgfmt) to be installed.
 */

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const gettextRoot = 'vendor/runalyze/translations/gettext';
const poPattern = /^messages\.(.+)\.po$/;

function walk(dir, out) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(full, out);
    } else if (entry.isFile()) {
      out.push(full);
    }
  }
  return out;
}

if (!fs.existsSync(gettextRoot)) {
  console.warn('Skipping translations: ' + gettextRoot + ' not found (run composer install first).');
  process.exit(0);
}

if (spawnSync('msgfmt', ['--version']).error) {
  console.error('msgfmt not found. Please install gettext first.');
  process.exit(1);
}

const poFiles = walk(gettextRoot, []).filter((f) => {
  const parts = path.relative(gettextRoot, f).split(path.sep);
  return parts.length === 4 && poPattern.test(parts[3]);
});

for (const po of poFiles) {
  fs.copyFileSync(po, path.join(gettextRoot, path.basename(po)));

  const mo = po.replace(poPattern, 'runalyze.mo');
  const result = spawnSync('msgfmt', ['-v', po, '-o', mo], { stdio: 'inherit' });

  if (result.status !== 0) {
    console.error('msgfmt failed for ' + po);
    process.exit(result.status || 1);
  }
  console.log('compiled ' + mo);
}

if (poFiles.length === 0) {
  console.warn('No .po files found in ' + gettextRoot + ' (expected <lang>/LC_MESSAGES/messages.<lang>.po).');
}
