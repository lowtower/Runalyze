<?php

namespace Runalyze\Parser\Activity\Common\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\WeatherData;

class WeatherDataMerger implements MergerInterface
{
    public function __construct(
        protected WeatherData $ResultingData,
        protected WeatherData $DataToMerge
    ) {}

    public function merge(): void
    {
        foreach ($this->getPropertiesToMerge() as $property) {
            if ($this->ResultingData->{$property} === null || $this->ResultingData->{$property} === '') {
                $this->ResultingData->{$property} = $this->DataToMerge->{$property};
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getPropertiesToMerge(): array
    {
        return [
            'Condition',
            'InternalConditionId',
            'Temperature',
            'WindSpeed',
            'WindDirection',
            'Humidity',
            'AirPressure',
        ];
    }
}
