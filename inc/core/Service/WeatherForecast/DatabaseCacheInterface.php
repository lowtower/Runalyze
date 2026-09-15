<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast;

use Runalyze\Parser\Activity\Common\Data\WeatherData;

interface DatabaseCacheInterface
{
    /**
     * @param int $timeTolerance [s]
     */
    public function getCachedWeatherDataFor(Location $location, int $timeTolerance): ?WeatherData;

    public function cacheWeatherData(WeatherData $data, Location $location);
}
