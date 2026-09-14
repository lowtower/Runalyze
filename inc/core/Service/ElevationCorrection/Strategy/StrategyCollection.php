<?php

declare(strict_types=1);

namespace Runalyze\Service\ElevationCorrection\Strategy;

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
     * @var array<string, class-string<\Throwable>>
     */
    protected array $LastAttemptExceptions = [];

    public function add(StrategyInterface $strategy): void
    {
        $this->Strategies[] = $strategy;
    }

    /**
     * @return array<string, string> strategy class name => reason key
     *                                ('not_possible' or 'no_data')
     */
    public function getLastAttemptReasons(): array
    {
        return $this->LastAttemptReasons;
    }

    /**
     * @return array<string, class-string<\Throwable>> strategy class name => exception class name
     */
    public function getLastAttemptExceptions(): array
    {
        return $this->LastAttemptExceptions;
    }

    /**
     * @return array|null altitude [m]
     *
     * @throws \InvalidArgumentException
     */
    public function loadAltitudeData(array $latitudes, array $longitudes): ?array
    {
        $this->LastAttemptReasons = [];
        $this->LastAttemptExceptions = [];

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

                $this->LastAttemptReasons[$strategy::class] = 'no_data';

                if ($strategy instanceof \Runalyze\Service\ElevationCorrection\Strategy\LastExceptionAwareInterface) {
                    $lastException = $strategy->getLastException();

                    if ($lastException instanceof \Throwable) {
                        $this->LastAttemptExceptions[$strategy::class] = $lastException::class;
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
