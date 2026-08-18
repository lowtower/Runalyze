<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast;

use Runalyze\Parser\Activity\Common\Data\WeatherData;

interface DatabaseCacheInterface
{
    /**
     * @param int $timeTolerance [s]
     * @return WeatherData|null
     */
    public function getCachedWeatherDataFor(Location $location, $timeTolerance);

    public function cacheWeatherData(WeatherData $data, Location $location);
}
