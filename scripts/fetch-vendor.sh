#!/usr/bin/env bash
#
# Fetches the frontend vendor packages into public/vendor.
#
# These files used to be installed by bower (.bowrrc: directory public/vendor).
# They are committed build inputs now (scripts/build-js.js and the LESS
# sources read them directly), so this script is only needed to bootstrap a
# checkout where files are missing - e.g. after a rebase wiped untracked
# files, or for a fresh clone.
#
# A package is skipped only if all of its build inputs are present; an
# existing but incomplete package directory is deleted and fetched again.
#
# Repos and tags mirror the former bower.json / bower registry exactly
# (d3 comes from the dedicated bower mirror repo, because the main d3 repo
# does not ship built files). Every repo, tag and build-input path below
# has been verified via the bower registry and raw.githubusercontent.com.
#
# Usage: bash scripts/fetch-vendor.sh   (needs curl + tar, no npm/node)
set -euo pipefail

VENDOR_DIR="public/vendor"
mkdir -p "$VENDOR_DIR"

# name                  repo                              tag
packages=(
  "jquery                 jquery/jquery-dist                3.6.4"
  "tablesorter            Mottie/tablesorter                v2.31.3"
  "leaflet                Leaflet/Leaflet                  v0.7.7"
  "d3                     mbostock-bower/d3-bower           v5.16.0"
  "d3-tip                 caged/d3-tip                     v0.8.0-alpha.1"
  "fontIconPicker         micc83/fontIconPicker             v2.0.0"
  "font-awesome           FortAwesome/Font-Awesome         5.15.4"
  "moment                 moment/moment                    2.30.1"
  "moment-timezone        moment/moment-timezone            0.6.3"
  "moment-duration-format jsmreese/moment-duration-format   2.3.2"
  "ifvisible.js           serkanyersen/ifvisible.js         v1.0.6"
  "jquery-timeago         rmm5t/jquery-timeago              v1.6.7"
  "regression-js          Tom-Alexander/regression-js       1.4.0"
  "pace                   CodeByZach/pace                   v1.2.4"
)

# Build inputs that each package must provide (space separated, relative to
# the package directory). Mirrors scripts/build-js.js and the LESS imports.
requirement_for() {
  case "$1" in
    jquery)                 echo "dist/jquery.js" ;;
    tablesorter)            echo "js/jquery.tablesorter.js addons/pager/jquery.tablesorter.pager.js" ;;
    leaflet)               echo "dist/leaflet.js dist/leaflet.css" ;;
    d3)                    echo "d3.js" ;;
    d3-tip)                echo "index.js" ;;
    fontIconPicker)        echo "jquery.fonticonpicker.js css/jquery.fonticonpicker.css themes/grey-theme/jquery.fonticonpicker.grey.css" ;;
    font-awesome)          echo "css/all.css" ;;
    moment)                echo "moment.js" ;;
    moment-timezone)       echo "builds/moment-timezone-with-data-1970-2030.min.js" ;;
    moment-duration-format) echo "lib/moment-duration-format.js" ;;
    ifvisible.js)          echo "src/ifvisible.js" ;;
    jquery-timeago)        echo "jquery.timeago.js" ;;
    regression-js)         echo "build/regression.min.js" ;;
    pace)                  echo "pace.min.js" ;;
    *)                     echo "" ;;
  esac
}

for entry in "${packages[@]}"; do
  read -r name repo tag <<< "$entry"
  target="$VENDOR_DIR/$name"

  needs_fetch=1
  reason="not present"
  if [ -d "$target" ]; then
    needs_fetch=0
    for f in $(requirement_for "$name"); do
      if [ ! -f "$target/$f" ]; then
        needs_fetch=1
        reason="incomplete (missing $f)"
        break
      fi
    done
  fi

  if [ "$needs_fetch" -eq 0 ]; then
    echo "skip  $name (complete)"
    continue
  fi

  echo "fetch $name - $reason ($repo @$tag)"
  [ -d "$target" ] && rm -rf "$target"
  tmp="$(mktemp -d)"
  if ! curl -fsSL "https://github.com/$repo/archive/refs/tags/$tag.tar.gz" | tar -xz -C "$tmp"; then
    echo "ERROR: failed to fetch $name from $repo @$tag" >&2
    rm -rf "$tmp"
    exit 1
  fi
  inner="$(ls -A "$tmp")"
  if [ "$(printf '%s\n' "$inner" | wc -l)" -ne 1 ]; then
    echo "ERROR: unexpected archive layout for $name" >&2
    rm -rf "$tmp"
    exit 1
  fi
  mv "$tmp/$inner" "$target"
  rm -rf "$tmp"

  # still incomplete after fetching? then the pin is wrong - fail loudly
  for f in $(requirement_for "$name"); do
    if [ ! -f "$target/$f" ]; then
      echo "ERROR: fetched $name@$tag does not provide $f" >&2
      exit 1
    fi
  done
done

# Nested .gitignore files from the vendor packages must not leak ignore
# rules into this repository (everything under public/vendor is tracked).
find "$VENDOR_DIR" -name .gitignore -type f -delete

# Final verification of every build input referenced by scripts/build-js.js
# and lib/less/runalyze-style.less
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
