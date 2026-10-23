<?php

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

class ContinuousDataAdapter
{
    public const int RPM_LIMIT_FOR_CORRECTION = 130;

    public function __construct(
        protected ContinuousData $ContinuousData
    ) {}

    public function calculateDistancesIfRequired(): void
    {
        if ($this->distancesShouldBeCalculated()) {
            new GpsDistanceCalculator()->calculateDistancesFor($this->ContinuousData);
        }
    }

    protected function distancesShouldBeCalculated(): bool
    {
        return
            $this->ContinuousData->Distance === []
            && $this->ContinuousData->Latitude !== []
            && $this->ContinuousData->Longitude !== []
        ;
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1367
     */
    public function correctCadenceIfRequired(): void
    {
        if ($this->ContinuousData->Cadence !== []) {
            $avg = array_sum($this->ContinuousData->Cadence) / count($this->ContinuousData->Cadence);

            if ($avg > self::RPM_LIMIT_FOR_CORRECTION) {
                $this->ContinuousData->Cadence = array_map(fn ($v): float => round($v / 2), $this->ContinuousData->Cadence);
            }
        }
    }

    /**
     * Corrects the problem that the GPS device has NULL values while GPS is not fully initializes while
     * activity is tracking.
     * so search for the first Latitude with NOT NULL and copy these value to the NULLs. Do this also with
     * the same range for Longitude (and Altitude). Hope that the two values has the same NULLs.
     * #TSC
     */
    public function correctPreNullGpsIfRequired(): void
    {
        if ($this->ContinuousData->Latitude !== []) {
            $size = count($this->ContinuousData->Latitude);
            // search for the index with the first NOT NULL value
            $firstNotNull = 0;
            while ($firstNotNull < $size && $this->ContinuousData->Latitude[$firstNotNull] === null) {
                $firstNotNull++;
            }

            if ($firstNotNull > 0 && $firstNotNull < $size) {
                for ($i = 0; $i < $firstNotNull; $i++) {
                    $this->ContinuousData->Latitude[$i] = $this->ContinuousData->Latitude[$firstNotNull];
                    $this->ContinuousData->Longitude[$i] = $this->ContinuousData->Longitude[$firstNotNull];
                }
            }
        }
    }

    /**
     * Ensure that on first/pre and mid/last values of a array there are no NULL values.
     * So search for the "first/last" valid value and replace the NULLs.
     * #TSC
     * @param array<int, mixed> $values
     */
    public function correctArraySurroundedNulls(array &$values): void
    {
        if ($values !== []) {
            $size = count($values);

            // pre NULLs

            // search for the index with the first NOT NULL value
            $firstNotNull = 0;
            while ($firstNotNull < $size && $values[$firstNotNull] === null) {
                $firstNotNull++;
            }

            // all values are NULL
            if ($firstNotNull >= $size) {
                return;
            }

            if ($firstNotNull > 0) {
                for ($i = 0; $i < $firstNotNull; $i++) {
                    $values[$i] = $values[$firstNotNull];
                }
            }

            // mid/post NULLs

            // we have set previously the beginning with values so idx0 must have a value!
            $notNull = $values[$firstNotNull];
            // now search from start-to-end and set the last not null to the nulls
            for ($i = $firstNotNull; $i < $size; $i++) {
                if ($values[$i] === null) {
                    $values[$i] = $notNull;
                } else {
                    $notNull = $values[$i];
                }
            }
        }
    }

    /**
     * Ensure that empty/NULL values in array (||) has a valid number/0 (|0|).
     * #TSC
     * @param array<int, mixed> $values
     */
    public function correctArrayNullElements(array &$values): void
    {
        if ($values !== []) {
            $size = count($values);

            for ($i = 0; $i < $size; $i++) {
                $values[$i] ??= 0;
            }
        }
    }

    public function filterUnwantedZeros(): void
    {
        foreach ($this->ContinuousData->getPropertyNamesOfArraysThatShouldNotContainZeros() as $key) {
            if (! empty($this->ContinuousData->{$key})) {
                foreach ($this->ContinuousData->{$key} as $i => $value) {
                    if ($value == 0) {
                        $this->ContinuousData->{$key}[$i] = null;
                    }
                }
            }
        }

        if ($this->ContinuousData->Distance !== [] && end($this->ContinuousData->Distance) == 0) {
            $this->ContinuousData->Distance = [];
        }

        if ($this->ContinuousData->Altitude !== [] && $this->ContinuousData->Altitude[0] == 0 && min($this->ContinuousData->Altitude) == 0 && max($this->ContinuousData->Altitude) == 0) {
            $this->ContinuousData->Altitude = [];
        }

        if ($this->ContinuousData->Latitude !== [] && $this->ContinuousData->Latitude[0] == 0 && min($this->ContinuousData->Latitude) == 0 && max($this->ContinuousData->Latitude) == 0) {
            $this->ContinuousData->Latitude = [];
        }

        if ($this->ContinuousData->Longitude !== [] && $this->ContinuousData->Longitude[0] == 0 && min($this->ContinuousData->Longitude) == 0 && max($this->ContinuousData->Longitude) == 0) {
            $this->ContinuousData->Longitude = [];
        }
    }

    public function clearEmptyArrays(): void
    {
        $arrayKeys = $this->ContinuousData->getPropertyNamesOfArrays();
        $arrayKeysWithData = [];
        $arraySize = null;

        foreach ($arrayKeys as $key) {
            if (! empty($this->ContinuousData->{$key})) {
                $arrayKeysWithData[] = $key;

                $arraySize ??= count($this->ContinuousData->{$key});
            }
        }

        for ($i = 0; $i < $arraySize; ++$i) {
            foreach ($arrayKeysWithData as $j => $key) {
                if (null !== $this->ContinuousData->{$key}[$i]) {
                    unset($arrayKeysWithData[$j]);
                }
            }
        }

        foreach ($arrayKeysWithData as $key) {
            $this->ContinuousData->{$key} = [];
        }
    }

    public function applyPauses(PauseCollection $pausesToApply): \Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection
    {
        $resultingPauses = new PauseCollection();

        $num = count($this->ContinuousData->Time);
        $numPauses = $pausesToApply->count();
        $keys = $this->ContinuousData->getPropertyNamesOfArrays();
        $hasHeartRate = $this->ContinuousData->HeartRate !== [];
        $hrStart = null;
        $pauseInSeconds = 0;
        $pauseIndex = 0;
        $pauseUntil = 0;
        $pauseTime = $pausesToApply[$pauseIndex]->getTimeIndex();
        $isPause = false;

        for ($i = 0; $i < $num; $i++) {
            if (! $isPause && $this->ContinuousData->Time[$i] > $pauseTime) {
                if ($pauseIndex < $numPauses) {
                    $isPause = true;
                    $hrStart = ! $hasHeartRate ? null : ($this->ContinuousData->HeartRate[$i - 1] ?? $this->ContinuousData->HeartRate[$i]);
                    $pauseInSeconds += $pausesToApply[$pauseIndex]->getDuration();
                    $pauseUntil = $pausesToApply[$pauseIndex]->getDuration() + $pausesToApply[$pauseIndex]->getTimeIndex();
                    $pauseIndex++;
                    $pauseTime = ($pauseIndex < $numPauses) ? $pausesToApply[$pauseIndex]->getTimeIndex() : PHP_INT_MAX;
                }
            }

            if ($isPause && $this->ContinuousData->Time[$i] >= $pauseUntil) {
                $isPause = false;
                $newPause = clone $pausesToApply[$pauseIndex - 1];
                $newPause->setHeartRateDetails($hrStart, $hasHeartRate ? $this->ContinuousData->HeartRate[$i] : null);

                $resultingPauses->add($newPause);
            }

            if ($isPause) {
                foreach ($keys as $key) {
                    if (array_key_exists($i, $this->ContinuousData->{$key})) {
                        unset($this->ContinuousData->{$key}[$i]);
                    }
                }
            } else {
                $this->ContinuousData->Time[$i] -= $pauseInSeconds;
            }
        }

        return $resultingPauses;
    }

    public function reIndexArrays(): void
    {
        foreach ($this->ContinuousData->getPropertyNamesOfArrays() as $key) {
            $this->ContinuousData->{$key} = array_merge($this->ContinuousData->{$key});
        }
    }

    /**
     * Returns the average temperature from continuous data if available.
     */
    public function getAverageTemperature(): ?float
    {
        if ($this->ContinuousData->Temperature === []) {
            return null;
        }

        $temperatures = array_filter(
            $this->ContinuousData->Temperature,
            fn ($temperature): bool => $temperature !== null
        );
        if ($temperatures === []) {
            return null;
        }

        return array_sum($temperatures) / count($temperatures);
    }
}
