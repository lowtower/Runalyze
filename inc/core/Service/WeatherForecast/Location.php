<?php

namespace Runalyze\Service\WeatherForecast;

use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;

class Location
{
    protected ?float $Latitude = null;

    protected ?float $Longitude = null;

    protected ?\DateTime $DateTime = null;

    protected string $LocationName = '';

    protected ?string $Timezone = null;

    public function setPosition(float $latitude, float $longitude): void
    {
        $this->Latitude = $latitude;
        $this->Longitude = $longitude;
    }

    public function setGeohash(string $geohash): void
    {
        try {
            $decoded = $this->coordinate($geohash);

            $this->Latitude = (float) $decoded->getLatitude();
            $this->Longitude = (float) $decoded->getLongitude();
        } catch (\InvalidArgumentException) {
            $this->Latitude = null;
            $this->Longitude = null;
        }
    }

    public function setDateTime(?\DateTime $dateTime = null): void
    {
        $this->DateTime = $dateTime;
    }

    public function setLocationName(string $location): void
    {
        $this->LocationName = $location;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->Timezone = $timezone;
    }

    public function getLatitude(): ?float
    {
        return $this->Latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->Longitude;
    }

    public function getCoordinate(): ?\League\Geotools\Coordinate\Coordinate
    {
        if ($this->hasPosition()) {
            return new Coordinate([(float) $this->getLatitude(), (float) $this->getLongitude()]);
        }

        return null;
    }

    public function getGeohash(): ?string
    {
        if ($this->hasPosition()) {
            /** @var Geohash $geohashObj */
            $geohashObj = (new Geohash())->encode($this->getCoordinate(), 12);
            return $geohashObj->getGeohash();
        }

        return null;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->DateTime;
    }

    public function getTimestamp(): ?int
    {
        if (! $this->DateTime instanceof \DateTime) {
            return null;
        }

        return $this->DateTime->getTimestamp();
    }

    public function getLocationName(): string
    {
        return $this->LocationName;
    }

    public function getTimezone(): ?string
    {
        return $this->Timezone;
    }

    /**
     * Is position set?
     */
    public function hasPosition(): bool
    {
        return
            $this->Latitude !== null &&
            $this->Longitude !== null &&
            ($this->Latitude !== 0.0 || $this->Longitude !== 0.0)
        ;
    }

    public function hasLocationName(): bool
    {
        return $this->LocationName !== '';
    }

    public function hasDateTime(): bool
    {
        return $this->DateTime instanceof \DateTime;
    }

    public function isOlderThan(int $seconds = 7200): bool
    {
        return $this->hasDateTime() && ($this->getTimestamp() < time() - $seconds);
    }

    /**
     * Check if location is valid for request a weather-forecast.
     * #TSC
     */
    public function isValid(): bool
    {
        return $this->hasDateTime() && ($this->hasLocationName() || $this->hasPosition());
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
