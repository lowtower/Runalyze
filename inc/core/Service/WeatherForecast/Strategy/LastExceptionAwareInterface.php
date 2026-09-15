<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast\Strategy;

interface LastExceptionAwareInterface
{
    public function getLastException(): ?\Throwable;
}
