<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast\Strategy;

use Runalyze\Service\WeatherForecast\Location;

class StrategyCollection
{
    /**
     * @var StrategyInterface[]
     */
    protected $Strategies = [];

    /**
     * @var bool|int
     */
    protected $LastSuccessfulStrategyIndex = false;

    public function add(StrategyInterface $strategy): void
    {
        $this->Strategies[] = $strategy;
    }

    /**
     * @return null|\Runalyze\Parser\Activity\Common\Data\WeatherData
     */
    public function tryToLoadForecast(Location $location)
    {
        foreach ($this->Strategies as $index => $strategy) {
            if ($strategy->isPossible()) {
                $result = $strategy->loadForecast($location);

                if ($result !== null) {
                    $this->LastSuccessfulStrategyIndex = $index;

                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @return null|StrategyInterface
     */
    public function getLastSuccessfulStrategy()
    {
        if ($this->LastSuccessfulStrategyIndex !== false) {
            return $this->Strategies[$this->LastSuccessfulStrategyIndex];
        }

        return null;
    }
}
