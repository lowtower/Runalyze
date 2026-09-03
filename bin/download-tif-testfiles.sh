#!/usr/bin/env bash
set -euo pipefail

mkdir -p data/srtm

for file in srtm_38_03 srtm_38_02 srtm_67_19 srtm_36_02 srtm_40_17 srtm_22_04
do
    for i in {1..3}; do
        tmp="data/srtm/${file}.zip"
        domain="https://srtm.csi.cgiar.org/wp-content/uploads/files/srtm_5x5/TIFF/${file}.zip"
        if [ ! -f data/srtm/${file}.tif ]; then
            if wget --progress=bar:force -O "$tmp" "$domain"; then
                if unzip -o data/srtm/${file}.zip ${file}.tif -d data/srtm; then
                    echo "✓ Downloaded srtm file < $file > successfully"
                fi
            else
                if [ $i -lt 3 ]; then
                    echo "⚠ Attempt $i failed, retrying in 5 seconds..."
                    sleep 5
                else
                    echo "⚠ Could not download srtm file < $file >"
                fi
            fi
        fi
    done
done

for file in n40_w075 n49_e007 n51_w001 s23_e017 s34_e151
do
    for i in {1..3}; do
        tmp="data/srtm/${file}_1arc_v3.tif"
        domain="https://cdn.runalyze.com/static/srtm1/${file}_1arc_v3.tif"
        if [ ! -f data/srtm/${file}_1arc_v3.tif ]; then
            if wget --progress=bar:force -O "$tmp" "$domain"; then
                echo "✓ Downloaded srtm file < $file > successfully"
            else
                if [ $i -lt 3 ]; then
                    echo "⚠ Attempt $i failed, retrying in 5 seconds..."
                    sleep 5
                else
                    echo "⚠ Could not download srtm file < $file >"
                fi
            fi
        fi
    done
done
