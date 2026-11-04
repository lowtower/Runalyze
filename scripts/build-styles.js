'use strict';

/*
 * Runalyze CSS build (phase 1 of the frontend build modernization).
 *
 * Replaces the old gulp less + gulp-clean-css pipeline:
 * - less: rewriteUrls 'all' (was relativeUrls: true), root path for imports
 * - clean-css: rebase relative URLs to public/assets/css (was rebaseTo: dest)
 */

const fs = require('fs');
const path = require('path');
const less = require('less');
const CleanCSS = require('clean-css');

const targets = [
  { src: 'lib/less/runalyze-style.less', root: 'lib/less', out: 'public/assets/css/runalyze-style.css' },
  { src: 'assets/css/runalyze-installer.less', root: 'assets/css', out: 'public/assets/css/runalyze-installer.css' }
];

const rebaseTo = path.resolve('public/assets/css');

(async () => {
  for (const t of targets) {
    const source = fs.readFileSync(t.src, 'utf8');

    const rendered = await less.render(source, {
      filename: path.resolve(t.src),
      paths: [path.resolve(t.root)],
      rewriteUrls: 'all'
    });

    const minified = new CleanCSS({ rebase: true, rebaseTo: rebaseTo }).minify(
      { [path.resolve(t.src)]: { styles: rendered.css } }
    );

    if (minified.errors && minified.errors.length) {
      throw new Error(t.src + ': ' + minified.errors.join('\n'));
    }

    fs.mkdirSync(path.dirname(t.out), { recursive: true });
    fs.writeFileSync(t.out, minified.styles);
    console.log('built ' + t.out);
  }
})().catch((e) => {
  console.error(e && e.message ? e.message : e);
  process.exit(1);
});
