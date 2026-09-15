<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast\Strategy;

use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;

interface StrategyInterface
{
    public function isPossible(): bool;

    public function isCachable(): bool;

    public function loadForecast(Location $location): ?WeatherData;
}
