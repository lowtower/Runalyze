#!/usr/bin/env bash
set -euo pipefail

# Downloading timezone database
echo "Downloading timezone database ..."
for i in {1..3}; do
    tmp="./data/timezone.sqlite"
    domain="https://cdn.runalyze.com/update/timezone.sqlite"
    if wget --progress=bar:force -O "$tmp" "$domain"; then
        echo "✓ Downloaded timezone database successfully"
        break
    else
        if [ $i -lt 3 ]; then
            echo "⚠ Attempt $i failed, retrying in 5 seconds..."
            sleep 5
        else
            echo "⚠ Could download timezone database, using existing version"
        fi
    fi
done
