<?php

/**
 * This file contains class::Location
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use League\Geotools\Coordinate\Coordinate;
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
     * @var float|null
     */
    protected $Latitude;

    /**
     * Longitude
     * @var float|null
     */
    protected $Longitude;

    /**
     * DateTime
     * @var \DateTime|null
     */
    protected $DateTime;

    /**
     * Location name
     * @var string
     */
    protected $LocationName = '';

    /**
     * @param float $latitude
     * @param float $longitude
     */
    public function setPosition($latitude, $longitude)
    {
        $this->Latitude = $latitude;
        $this->Longitude = $longitude;
    }

    /**
     * @param string $geohash
     */
    public function setGeohash($geohash)
    {
        $decoded = (new Geohash())->decode($geohash)
            ->getCoordinate();
        $this->Latitude = $decoded->getLatitude();
        $this->Longitude = $decoded->getLongitude();
    }

    /**
     * @param \DateTime|null $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->DateTime = $dateTime;
    }

    /**
     * @param string $location
     */
    public function setLocationName($location)
    {
        $this->LocationName = $location;
    }

    /**
     * Latitude
     * @return float
     */
    public function lat()
    {
        return $this->Latitude;
    }

    /**
     * Longitude
     * @return float
     */
    public function lon()
    {
        return $this->Longitude;
    }

    /**
     * @return string
     */
    public function geohash()
    {
        if ($this->hasPosition()) {
            return (new Geohash())->encode(new Coordinate([(float) $this->lat(), (float) $this->lon()]), 12)->getGeohash();
        }
    }

    /**
     * Time
     * @return \DateTime|null
     */
    public function dateTime()
    {
        return $this->DateTime;
    }

    /**
     * @return int
     */
    public function timestamp()
    {
        return $this->DateTime->getTimestamp();
    }

    /**
     * Location name
     * @return string
     */
    public function name()
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
        return (string) $this->LocationName !== '';
    }

    /**
     * Is position set?
     */
    public function hasDateTime(): bool
    {
        return $this->DateTime !== null;
    }

    /**
     * Is the location old?
     * @param int $seconds
     * @return bool true if the timestamp is older than 24 hours
     */
    public function isOlderThan($seconds = DAY_IN_S): bool
    {
        return $this->hasDateTime() && ($this->timestamp() < time() - $seconds);
    }
}
