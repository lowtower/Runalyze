<?php

namespace Runalyze\Calculation;

use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;
use Runalyze\Model\Activity;
use Runalyze\Model\Route;
use Runalyze\Util\LocalTime;

class NightDetector
{
    /**
     * @var float
     */
    const ZENITH = 90.833333333333;

    /**
     * @var null|int null: unknown, 0: at sunlight, 1: at night
     */
    protected $Value;

    /**
     * @param bool|int $timestamp
     */
    public function __construct($timestamp = false, ?CoordinateInterface $coordinate = null)
    {
        if ($timestamp !== false && $coordinate instanceof \League\Geotools\Coordinate\CoordinateInterface) {
            $this->setFrom($timestamp, $coordinate);
        }
    }

    /**
     * @param int $timestamp
     * @return \Runalyze\Calculation\NightDetector $this-reference
     * @throws \InvalidArgumentException
     */
    public function setFrom($timestamp, CoordinateInterface $coordinate): self
    {
        if (! is_numeric($timestamp)) {
            throw new \InvalidArgumentException('Provided timestamp must be numerical.');
        }

        $isAfterSunset = $timestamp > date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $coordinate->getLatitude(), $coordinate->getLongitude(), self::ZENITH);
        $isBeforeSunrise = $timestamp < date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $coordinate->getLatitude(), $coordinate->getLongitude(), self::ZENITH);

        $this->Value = $isAfterSunset || $isBeforeSunrise;

        return $this;
    }

    /**
     * @return \Runalyze\Calculation\NightDetector $this-reference
     */
    public function setFromEntities(Activity\Entity $activity, Route\Entity $route): self
    {
        if ($route->hasGeohashes() && $route->get(Route\Entity::STARTPOINT) != '') {
            // TODO use activity's offset if known
            $timestamp = (new LocalTime($activity->timestamp()))->toServerTimestamp() + 0.5 * $activity->duration();
            $coordinate = (new Geohash())->decode($route->get(Route\Entity::STARTPOINT))->getCoordinate();

            $this->setFrom($timestamp, $coordinate);
        } else {
            $this->Value = null;
        }

        return $this;
    }

    public function isKnown(): bool
    {
        return $this->Value !== null;
    }

    public function isNight(): bool
    {
        return $this->Value == 1;
    }

    /**
     * @return int|null null: unknown, 0: at sunlight, 1: at night
     */
    public function value()
    {
        return $this->Value;
    }
}
