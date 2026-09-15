<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast\Strategy;

use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\DatabaseCacheInterface;
use Runalyze\Service\WeatherForecast\Location;

class DatabaseCache implements StrategyInterface
{
    /**
     * @var int [s] time range for cache lookup (in seconds) (+/- 30 min)
     */
    public const TIME_PRECISION = 1800;

    public function __construct(
        protected DatabaseCacheInterface $Cache
    ) {
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function isCachable(): bool
    {
        return false;
    }

    public function loadForecast(Location $location): ?WeatherData
    {
        return $this->Cache->getCachedWeatherDataFor($location, self::TIME_PRECISION);
    }
}
