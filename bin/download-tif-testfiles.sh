#!/usr/bin/env bash
set -euo pipefail

mkdir -p data/srtm

# Downloading SRTM files with retry logic
echo "Downloading SRTM files database ..."

# List of SRTM files to download
srtm_files=(
    "srtm_38_03 srtm_38_02 srtm_67_19 srtm_36_02 srtm_40_17 srtm_22_04"
    "n40_w075 n49_e007 n51_w001 s23_e017 s34_e151"
)

for group in "${srtm_files[@]}"; do
    for file in $group; do
        if [[ $file == *"_1arc_v3.tif" ]]; then
            # Handle files with _1arc_v3.tif suffix
            output_file="data/srtm/${file}"
            url="https://cdn.runalyze.com/static/srtm1/${file}"
        else
            # Handle regular SRTM files
            output_file="data/srtm/${file}.tif"
            url="https://srtm.csi.cgiar.org/wp-content/uploads/files/srtm_5x5/TIFF/${file}.zip"
        fi

        if [ ! -f "$output_file" ]; then
            attempt=1
            success=false

            while [ $attempt -le 3 ]; do
                if [[ $file == *"_1arc_v3.tif" ]]; then
                    if wget --progress=bar:force -O "$output_file" "$url"; then
                        echo "✓ Downloaded ${file} successfully"
                        success=true
                        break
                    fi
                else
                    tmp_zip="data/srtm/${file}.zip"
                    if curl -L "$url" -o "$tmp_zip"; then
                        if unzip -o "$tmp_zip" "${file}.tif" -d data/srtm; then
                            rm -f "$tmp_zip"
                            echo "✓ Downloaded and extracted ${file} successfully"
                            success=true
                            break
                        fi
                    fi
                fi

                if [ $attempt -lt 3 ]; then
                    echo "⚠ Attempt $attempt failed for ${file}, retrying in 5 seconds..."
                    sleep 5
                fi
                ((attempt++))
            done

            if [ "$success" = false ]; then
                echo "⚠ Could not download ${file}, skipping..."
            fi
        else
            echo "✓ ${file} already exists, skipping download"
        fi
    done
done
