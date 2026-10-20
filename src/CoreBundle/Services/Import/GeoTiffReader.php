<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\DEM\Interpolation\BilinearInterpolation;
use Runalyze\DEM\Provider\GeoTIFF\SRTM4Provider;
use Runalyze\DEM\Reader;

class GeoTiffReader extends Reader
{
    public function __construct(string $directory)
    {
        parent::__construct(
            new SRTM4Provider($directory, new BilinearInterpolation())
        );
    }
}
