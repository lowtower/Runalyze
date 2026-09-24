<?php

declare(strict_types=1);

namespace Runalyze\Mathematics\PointReduction;

abstract class AbstractPointReductionAlgorithm
{
    protected array $ReducedX = [];

    protected array $ReducedY = [];

    protected array $ReducedIndices = [];

    public function getReducedX(): array
    {
        return $this->ReducedX;
    }

    public function getReducedY(): array
    {
        return $this->ReducedY;
    }

    public function getReducedIndices(): array
    {
        return $this->ReducedIndices;
    }

    public static function perpendicularDistance(float $pointX, float $pointY, float $line1x, float $line1y, float $line2x, float $line2y): float
    {
        if ($line2x === $line1x) {
            return abs($pointX - $line2x);
        }

        $slope = ($line2y - $line1y) / ($line2x - $line1x);
        $passThroughY = -$line1x * $slope + $line1y;

        return abs(($slope * $pointX) - $pointY + $passThroughY) / (sqrt($slope * $slope + 1));
    }

    public static function shortestDistance(float $pointX, float $pointY, float $line1x, float $line1y, float $line2x, float $line2y): float
    {
        $lineLength = self::pointDistance($line1x, $line1y, $line2x, $line2y);

        if ($lineLength === 0.0) {
            return self::pointDistance($pointX, $pointY, $line1x, $line1y);
        }

        $t = (($pointX - $line1x) * ($line2x - $line1x) + ($pointY - $line1y) * ($line2y - $line1y)) / ($lineLength * $lineLength);

        if ($t < 0) {
            return self::pointDistance($pointX, $pointY, $line1x, $line1y);
        }

        if ($t > 1) {
            return self::pointDistance($pointX, $pointY, $line2x, $line2y);
        }

        return self::pointDistance($pointX, $pointY, $line1x + $t * ($line2x - $line1x), $line1y + $t * ($line2y - $line1y));
    }

    public static function pointDistance(float $point1x, float $point1y, float $point2x, float $point2y): float
    {
        return sqrt(($point2x - $point1x) * ($point2x - $point1x) + ($point2y - $point1y) * ($point2y - $point1y));
    }
}
