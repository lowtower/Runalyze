#!/usr/bin/env bash
#
# Fetches the frontend vendor packages into public/vendor.
#
# These files used to be installed by bower (.bowerrc: directory public/vendor).
# They are committed build inputs now (scripts/build-js.js and the LESS
# sources read them directly), so this script is only needed to bootstrap a
# checkout where they are missing - e.g. after a rebase wiped the untracked
# files, or for a fresh clone.
#
# Versions mirror the former bower.json exactly; every repo/tag below was
# verified to contain the file paths referenced by scripts/build-js.js and
# lib/less/runalyze-style.less.
#
# Usage: bash scripts/fetch-vendor.sh   (needs curl + tar, no npm/node)
set -euo pipefail

VENDOR_DIR="public/vendor"
mkdir -p "$VENDOR_DIR"

# name                  repo                              tag
packages=(
  "jquery                 jquery/jquery-dist                3.6.4"
  "tablesorter            tablesorter/tablesorter          2.31.3"
  "leaflet                Leaflet/Leaflet                  0.7.7"
  "d3                     d3/d3                            v5.16.0"
  "d3-tip                 caged/d3-tip                     v0.8.0-alpha.1"
  "fontIconPicker         fontIconPicker/fontIconPicker    v2.0.0"
  "font-awesome           FortAwesome/Font-Awesome         5.15.4"
  "moment                 moment/moment                    2.30.1"
  "moment-timezone        moment/moment-timezone           0.6.3"
  "moment-duration-format moment/moment-duration-format     2.3.2"
  "ifvisible.js           elclanrs/ifvisible.js            1.0.6"
  "jquery-timeago         rmm5r/jquery-timeago             v1.6.7"
  "regression-js          Tom-Alexander/regression         v1.4.0"
  "pace                   CodeByZach/pace                  v1.2.4"
)

for entry in "${packages[@]}"; do
  read -r name repo tag <<< "$entry"
  target="$VENDOR_DIR/$name"
  if [ -d "$target" ]; then
    echo "skip  $name (already present)"
    continue
  fi
  echo "fetch $name ($repo @$tag)"
  tmp="$(mktemp -d)"
  curl -fsSL "https://github.com/$repo/archive/refs/tags/$tag.tar.gz" | tar -xz -C "$tmp"
  inner="$(ls -A "$tmp")"
  if [ "$(printf '%s\n' "$inner" | wc -l)" -ne 1 ]; then
    echo "ERROR: unexpected archive layout for $name" >&2
    rm -rf "$tmp"
    exit 1
  fi
  mv "$tmp/$inner" "$target"
  rm -rf "$tmp"
done

# Verify the build inputs referenced by scripts/build-js.js and the LESS sources
required=(
  jquery/dist/jquery.js
  tablesorter/js/jquery.tablesorter.js
  tablesorter/addons/pager/jquery.tablesorter.pager.js
  leaflet/dist/leaflet.js
  leaflet/dist/leaflet.css
  d3/d3.js
  d3-tip/index.js
  fontIconPicker/jquery.fonticonpicker.js
  fontIconPicker/css/jquery.fonticonpicker.css
  fontIconPicker/themes/grey-theme/jquery.fonticonpicker.grey.css
  font-awesome/css/all.css
  moment/moment.js
  moment-timezone/builds/moment-timezone-with-data-1970-2030.min.js
  moment-duration-format/lib/moment-duration-format.js
  ifvisible.js/src/ifvisible.js
  jquery-timeago/jquery.timeago.js
  regression-js/build/regression.min.js
  pace/pace.min.js
)

fail=0
for f in "${required[@]}"; do
  if [ ! -f "$VENDOR_DIR/$f" ]; then
    echo "MISSING: $VENDOR_DIR/$f" >&2
    fail=1
  fi
done

if [ "$fail" -ne 0 ]; then
  exit 1
fi

echo "vendor files OK in $VENDOR_DIR"
