<?php

namespace Runalyze\Calculation;

use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;
use Runalyze\Model\Activity;
use Runalyze\Model\Route;
use Runalyze\Util\LocalTime;

class NightDetector
{
    public const float ZENITH = 90.833333333333;

    /**
     * @var ?int null: unknown, 0: at sunlight, 1: at night
     */
    protected ?int $Value = null;

    public function __construct(bool|int $timestamp = false, ?CoordinateInterface $coordinate = null)
    {
        if ($timestamp !== false && $coordinate instanceof CoordinateInterface) {
            $this->setFrom($timestamp, $coordinate);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setFrom(?int $timestamp, CoordinateInterface $coordinate): self
    {
        if (! is_numeric($timestamp)) {
            throw new \InvalidArgumentException('Provided timestamp must be numerical.');
        }

        $sunInfo = date_sun_info($timestamp, (float) $coordinate->getLatitude(), (float) $coordinate->getLongitude());
        $isAfterSunset = $timestamp > $sunInfo['sunset'];
        $isBeforeSunrise = $timestamp < $sunInfo['sunrise'];

        $this->Value = (int) ($isAfterSunset || $isBeforeSunrise);

        return $this;
    }

    public function setFromEntities(Activity\Entity $activity, Route\Entity $route): self
    {
        if ($route->hasGeohashes() && $route->get(Route\Entity::STARTPOINT) != '') {
            // TODO use activity's offset if known
            $timestamp = new LocalTime($activity->timestamp())->toServerTimestamp() + (int) round(0.5 * $activity->duration());
            $coordinate = $this->coordinate($route->get(Route\Entity::STARTPOINT));

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
     * @return ?int null: unknown, 0: at sunlight, 1: at night
     */
    public function value(): ?int
    {
        return $this->Value;
    }

    private function coordinate(string $geohash): CoordinateInterface
    {
        return $this->decodedGeohash($geohash)
            ->getCoordinate();
    }

    private function decodedGeohash(string $geohash): Geohash
    {
        /** @var Geohash $decoded */
        $decoded = new Geohash()->decode($geohash);

        return $decoded;
    }
}
