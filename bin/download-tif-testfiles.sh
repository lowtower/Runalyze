#!/bin/sh
set -ex

mkdir -p data/srtm

for file in srtm_38_03 srtm_38_02 srtm_67_19 srtm_36_02 srtm_40_17 srtm_22_04
do
    if [ ! -f data/srtm/${file}.tif ]
    then
        curl https://srtm.csi.cgiar.org/wp-content/uploads/files/srtm_5x5/TIFF/${file}.zip > data/srtm/${file}.zip
        unzip -o data/srtm/${file}.zip ${file}.tif -d data/srtm
        rm -rf data/srtm/${file}.zip
    fi
done

for file in n40_w075 n49_e007 n51_w001 s23_e017 s34_e151
do
    if [ ! -f data/srtm/${file}_1arc_v3.tif ]
    then
        curl https://cdn.runalyze.com/static/srtm1/${file}_1arc_v3.tif > data/srtm/${file}_1arc_v3.tif
    fi
done
