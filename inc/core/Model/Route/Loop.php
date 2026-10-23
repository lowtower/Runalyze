<?php

/**
 * This file contains class::Loop
 * @package Runalyze\Model\Route
 */

namespace Runalyze\Model\Route;

use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;
use Runalyze\Model\Loopable;

/**
 * Loop through route object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Route
 */
class Loop extends \Runalyze\Model\Loop
{
    #[\Override]
    protected ?Loopable $Object = null;

    public function __construct(?Entity $object)
    {
        parent::__construct($object);
    }

    /**
     * Current latitude
     */
    public function latitude(): string
    {
        return $this->coordinate()
            ->getLatitude();
    }

    /**
     * Current longitude
     */
    public function longitude(): string
    {
        return $this->coordinate()
            ->getLongitude();
    }

    /**
     * Current geohash
     */
    public function geohash(): string
    {
        return (string) $this->current(Entity::GEOHASHES);
    }

    public function coordinate(): CoordinateInterface
    {
        $decoded = $this->decodedGeohash($this->geohash());

        return $decoded->getCoordinate();
    }

    /**
     * Calculate distance of current step from latitude/longitude
     */
    public function calculatedStepDistance(): float
    {
        $LastGeohash = $this->decodedGeohash($this->Object->at($this->LastIndex, Entity::GEOHASHES))->getCoordinate();
        $IndexGeohash = $this->decodedGeohash($this->Object->at($this->Index, Entity::GEOHASHES))->getCoordinate();

        return Entity::gpsDistance(
            (float) $LastGeohash->getLatitude(),
            (float) $LastGeohash->getLongitude(),
            (float) $IndexGeohash->getLatitude(),
            (float) $IndexGeohash->getLongitude()
        );
    }

    public function sliceElevation(): array
    {
        if (method_exists($this->Object, 'hasCorrectedElevations') && $this->Object->hasCorrectedElevations()) {
            return $this->slice(Entity::ELEVATIONS_CORRECTED);
        }

        if (method_exists($this->Object, 'hasOriginalElevations') && $this->Object->hasOriginalElevations()) {
            return $this->slice(Entity::ELEVATIONS_ORIGINAL);
        }

        return [];
    }

    protected function createNewObject(array $data): \Runalyze\Model\Route\Entity
    {
        return new Entity($data);
    }
}
