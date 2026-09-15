<?php

declare(strict_types=1);

namespace Runalyze\Service\WeatherForecast\Strategy;

use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;

class StrategyCollection
{
    /**
     * @var StrategyInterface[]
     */
    protected array $Strategies = [];

    /**
     * @var bool|int
     */
    protected $LastSuccessfulStrategyIndex = false;

    /**
     * @var array<string, string> strategy class name => reason key
     */
    protected array $LastAttemptReasons = [];

    /**
     * @var array<string, \Throwable>
     */
    protected array $LastAttemptExceptions = [];

    public function add(StrategyInterface $strategy): void
    {
        $this->Strategies[] = $strategy;
    }

    /**
     * @return array<string, string> strategy class name => 'not_possible'|'no_data'
     */
    public function getLastAttemptReasons(): array
    {
        return $this->LastAttemptReasons;
    }

    /**
     * @return array<string, \Throwable> strategy class name => exception
     */
    public function getLastAttemptExceptions(): array
    {
        return $this->LastAttemptExceptions;
    }

    public function tryToLoadForecast(Location $location): ?WeatherData
    {
        $this->LastAttemptReasons = [];
        $this->LastAttemptExceptions = [];

        foreach ($this->Strategies as $index => $strategy) {
            if ($strategy->isPossible()) {
                $result = $strategy->loadForecast($location);

                if ($result !== null) {
                    $this->LastSuccessfulStrategyIndex = $index;

                    return $result;
                }

                $this->LastAttemptReasons[$strategy::class] = 'no_data';

                if ($strategy instanceof LastExceptionAwareInterface) {
                    $lastException = $strategy->getLastException();

                    if ($lastException instanceof \Throwable) {
                        $this->LastAttemptExceptions[$strategy::class] = $lastException;
                    }
                }
            } else {
                $this->LastAttemptReasons[$strategy::class] = 'not_possible';
            }
        }

        return null;
    }

    public function getLastSuccessfulStrategy(): ?StrategyInterface
    {
        if ($this->LastSuccessfulStrategyIndex !== false) {
            return $this->Strategies[$this->LastSuccessfulStrategyIndex];
        }

        return null;
    }
}
