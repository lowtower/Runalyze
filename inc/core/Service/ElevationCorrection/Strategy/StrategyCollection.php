<?php

declare(strict_types=1);

namespace Runalyze\Service\ElevationCorrection\Strategy;

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
     * @return array|null altitude [m]
     *
     * @throws \InvalidArgumentException
     */
    public function loadAltitudeData(array $latitudes, array $longitudes)
    {
        if ($latitudes === []) {
            return null;
        }

        if (count($latitudes) !== count($longitudes)) {
            throw new \InvalidArgumentException('Latitudes and longitudes must be of same size.');
        }

        foreach ($this->Strategies as $index => $strategy) {
            if ($strategy->isPossible()) {
                $result = $strategy->loadAltitudeData($latitudes, $longitudes);

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
