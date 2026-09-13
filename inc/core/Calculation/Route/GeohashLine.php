<?php

namespace Runalyze\Calculation\Route;

use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;

class GeohashLine
{
    public static function extend(array $geolineHashes): array
    {
        $geohashes = [];

        foreach ($geolineHashes as $hash) {
            if (empty($hash)) {
                $geohashes[] = end($geohashes) ?: '7zzzzzzzzzzz';
            } else {
                $geohashes[] = substr(end($geohashes), 0, 12 - strlen($hash)) . $hash;
            }
        }

        return $geohashes;
    }

    public static function shorten(array $geolineHashes): array
    {
        $last = '';
        $newgeoline = [];

        foreach ($geolineHashes as $hash) {
            if ($last == $hash) {
                $newgeoline[] = '';
            } elseif ($newgeoline === []) {
                $newgeoline[] = $hash;
            } else {
                $newgeoline[] = substr($hash, strspn($last ^ $hash, "\0"));
            }

            $last = $hash;
        }

        return $newgeoline;
    }

    public static function findFirstNonNullGeohash(array $geohashes, $precision): ?string
    {
        $nullGeohash = (new Geohash())->encode(new Coordinate([0.0, 0.0]), $precision)->getGeohash();

        foreach ($geohashes as $geohash) {
            $geohash = substr($geohash, 0, $precision);

            if ($geohash != $nullGeohash) {
                return $geohash;
            }
        }

        return null;
    }
}
