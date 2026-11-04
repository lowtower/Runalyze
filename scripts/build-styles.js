'use strict';

/*
 * Runalyze CSS build (phase 1 of the frontend build modernization).
 *
 * Replaces the old gulp less + gulp-clean-css pipeline:
 * - less: rewriteUrls 'all' (was relativeUrls: true), root path for imports
 * - clean-css: rebase relative URLs to public/assets/css (was rebaseTo: dest)
 *
 * Before rendering, every relative @import in the LESS source is checked on
 * disk. The vendor CSS files under public/vendor are untracked bower
 * artifacts on legacy checkouts; a rebase (git stash / git clean) can wipe
 * them, so the build refuses early with an actionable message instead of
 * emitting broken CSS.
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

function checkRelativeImports(target, source) {
  const srcDir = path.dirname(path.resolve(target.src));
  const specs = [...source.matchAll(/@import\s+(['"])(\.[^'"]+)\1/g)].map((m) => m[2]);
  const missing = specs.filter((spec) => !fs.existsSync(path.resolve(srcDir, spec)));

  if (missing.length > 0) {
    throw new Error(
      target.src + ': missing @import target(s), relative to ' + srcDir + ':\n  ' +
      missing.join('\n  ') +
      '\n\nIf a path under public/vendor is affected: those are bower-fetched build inputs. ' +
      'They are untracked on legacy checkouts, so a rebase (git stash / git clean) may have removed them.\n' +
      'Check "git stash list" (and pop if needed), or restore the files, then commit them once:\n' +
      '  git add public/vendor && git commit -m "Vendor bower assets as committed build inputs"'
    );
  }
}

(async () => {
  for (const t of targets) {
    const source = fs.readFileSync(t.src, 'utf8');

    checkRelativeImports(t, source);

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
