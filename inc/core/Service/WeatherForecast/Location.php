<?php

namespace Runalyze\Service\WeatherForecast;

use League\Geotools\Geohash\Geohash;
use League\Geotools\Coordinate\Coordinate;

class Location
{
    /**
     * @var float|null
     */
    protected $Latitude;

    /**
     * @var float|null
     */
    protected $Longitude;

    /**
     * @var \DateTime|null
     */
    protected $DateTime;

    /**
     * @var string
     */
    protected $LocationName = '';

    /**
     * @var string|null
     */
    protected $Timezone;

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
     * @param string|mixed $geohash
     */
    public function setGeohash($geohash)
    {
        try {
            $decoded = (new Geohash())->decode($geohash)
                ->getCoordinate();

            $this->Latitude = $decoded->getLatitude();
            $this->Longitude = $decoded->getLongitude();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->Latitude = null;
            $this->Longitude = null;
        }
    }

    public function setDateTime(?\DateTime $dateTime = null)
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
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->Timezone = $timezone;
    }

    /**
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->Latitude;
    }

    /**
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->Longitude;
    }

    /**
     * @return Coordinate|null
     */
    public function getCoordinate()
    {
        if ($this->hasPosition()) {
            return new Coordinate([(float) $this->getLatitude(), (float) $this->getLongitude()]);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getGeohash()
    {
        if ($this->hasPosition()) {
            return (new Geohash())->encode($this->getCoordinate(), 12)
                ->getGeohash();
        }

        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTime()
    {
        return $this->DateTime;
    }

    /**
     * @return int|null
     */
    public function getTimestamp()
    {
        if ($this->DateTime === null) {
            return null;
        }

        return $this->DateTime->getTimestamp();
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->LocationName;
    }

    /**
     * @return string|null
     */
    public function getTimezone()
    {
        return $this->Timezone;
    }

    /**
     * Is position set?
     */
    public function hasPosition(): bool
    {
        return (
            $this->Latitude !== null &&
            $this->Longitude !== null &&
            ($this->Latitude != 0.0 || $this->Longitude != 0.0)
        );
    }

    public function hasLocationName(): bool
    {
        return (string) $this->LocationName !== '';
    }

    public function hasDateTime(): bool
    {
        return $this->DateTime !== null;
    }

    /**
     * @param int $seconds
     */
    public function isOlderThan($seconds = 86400): bool
    {
        return $this->hasDateTime() && ($this->getTimestamp() < time() - $seconds);
    }

    /**
     * Check if location is valid for request a weather-forecast.
     * #TSC
     */
    public function isValid(): bool {
        return $this->hasDateTime() && ($this->hasLocationName() || $this->hasPosition());
    }
}
