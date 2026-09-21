<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Mapping\EnglishTextMapping;

class WeatherDataToActivityConverter
{
    public function setActivityWeatherDataFor(Training $activity, WeatherData $weatherData): void
    {
        if ($weatherData->isEmpty()) {
            return;
        }

        $activity->setTemperature($this->getRoundedValue($weatherData->Temperature));
        $activity->setWindSpeed($this->getRoundedValue($weatherData->WindSpeed));
        $activity->setWindDeg($weatherData->WindDirection);
        $activity->setHumidity($weatherData->Humidity);
        $activity->setPressure($this->getRoundedValue($weatherData->AirPressure));
        $activity->setWeatherSource($weatherData->Source);

        if ($weatherData->InternalConditionId !== null) {
            $activity->setWeatherid($weatherData->InternalConditionId);
        } elseif ($weatherData->Condition !== '') {
            $activity->setWeatherid((new EnglishTextMapping())->toInternal($weatherData->Condition));
        }
    }

    /**
     * @param mixed $value
     */
    protected function getRoundedValue($value): ?int
    {
        return $value !== null ? (int) round($value) : null;
    }
}
