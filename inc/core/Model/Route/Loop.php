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
    /**
     * @var null|Entity|Loopable
     */
    protected $Object;

    public function __construct(?Entity $object)
    {
        parent::__construct($object);
    }

    /**
     * Current latitude
     */
    public function latitude(): float
    {
        return (new Geohash())->decode($this->current(Entity::GEOHASHES))->getCoordinate()->getLatitude();
    }

    /**
     * Current longitude
     */
    public function longitude(): float
    {
        return (new Geohash())->decode($this->current(Entity::GEOHASHES))->getCoordinate()->getLongitude();
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
        return (new Geohash())->decode($this->current(Entity::GEOHASHES))->getCoordinate();
    }

    /**
     * Calculate distance of current step from latitude/longitude
     */
    public function calculatedStepDistance(): float
    {
        $LastGeohash = (new Geohash())->decode($this->Object->at($this->LastIndex, Entity::GEOHASHES))->getCoordinate();
        $IndexGeohash = (new Geohash())->decode($this->Object->at($this->Index, Entity::GEOHASHES))->getCoordinate();

        return Entity::gpsDistance(
            $LastGeohash->getLatitude(),
            $LastGeohash->getLongitude(),
            $IndexGeohash->getLatitude(),
            $IndexGeohash->getLongitude()
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
