<?php

/**
 * This file contains class::Location
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;

/**
 * Weather location
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Weather
 */
class Location
{
    /**
     * Latitude
     */
    protected ?float $Latitude = null;

    /**
     * Longitude
     */
    protected ?float $Longitude = null;

    /**
     * DateTime
     */
    protected ?\DateTime $DateTime = null;

    /**
     * Location name
     */
    protected string $LocationName = '';

    public function setPosition(float $latitude, float $longitude): void
    {
        $this->Latitude = $latitude;
        $this->Longitude = $longitude;
    }

    public function setGeohash(string $geohash): void
    {
        $decoded = $this->coordinate($geohash);

        $this->Latitude = (float) $decoded->getLatitude();
        $this->Longitude = (float) $decoded->getLongitude();
    }

    public function setDateTime(?\DateTime $dateTime): void
    {
        $this->DateTime = $dateTime;
    }

    public function setLocationName(string $location): void
    {
        $this->LocationName = $location;
    }

    /**
     * Latitude
     */
    public function lat(): float
    {
        return $this->Latitude;
    }

    /**
     * Longitude
     */
    public function lon(): float
    {
        return $this->Longitude;
    }

    public function geohash(): ?string
    {
        if (! $this->hasPosition()) {
            return null;
        }
        /** @var Geohash $geohashObj */
        $geohashObj = (new Geohash())->encode(new Coordinate([$this->lat(), $this->lon()]), 12);
        return $geohashObj->getGeohash();
    }

    /**
     * Time
     */
    public function dateTime(): ?\DateTime
    {
        return $this->DateTime;
    }

    public function timestamp(): int
    {
        return $this->DateTime->getTimestamp();
    }

    /**
     * Location name
     */
    public function name(): string
    {
        return $this->LocationName;
    }

    /**
     * Is position set?
     */
    public function hasPosition(): bool
    {
        return
            $this->Latitude !== null &&
            $this->Longitude !== null &&
            ($this->Latitude != 0 || $this->Longitude != 0)
        ;
    }

    /**
     * Has location name?
     */
    public function hasLocationName(): bool
    {
        return $this->LocationName !== '';
    }

    /**
     * Is position set?
     */
    public function hasDateTime(): bool
    {
        return $this->DateTime instanceof \DateTime;
    }

    /**
     * Is the location old?
     * @return bool true if the timestamp is older than 24 hours
     */
    public function isOlderThan(int $seconds = DAY_IN_S): bool
    {
        return $this->hasDateTime() && ($this->timestamp() < time() - $seconds);
    }

    private function coordinate(string $geohash): CoordinateInterface
    {
        return $this->decodedGeohash($geohash)
            ->getCoordinate();
    }

    private function decodedGeohash(string $geohash): Geohash
    {
        /** @var Geohash $decoded */
        $decoded = (new Geohash())->decode($geohash);

        return $decoded;
    }
}
