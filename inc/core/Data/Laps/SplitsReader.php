<?php

/**
 * This file contains class::SplitsReader
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Model\Activity\Splits;

/**
 * Read laps from splits object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class SplitsReader
{
    public function __construct(
        protected Laps $Laps
    ) {
    }

    public function readFrom(Splits\Entity $Splits)
    {
        foreach ($Splits->asArray() as $Split) {
            $this->Laps->add(new Lap(
                $Split->time(),
                $Split->distance(),
                $Split->getIntensity()
            ));
        }
    }
}
