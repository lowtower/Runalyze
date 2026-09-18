<?php

declare(strict_types=1);

/**
 * This file contains class::Laps
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Model\Activity\Splits;
use Runalyze\Model\Route;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Trackdata;

/**
 * Object of laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Laps
{
    /**
     * @var \Runalyze\Data\Laps\Lap[]
     */
    protected array $Objects;

    protected bool $CalculateAdditionalValues = false;

    public function enableCalculationOfAdditionalValues(bool $flag = true): void
    {
        $this->CalculateAdditionalValues = $flag;
    }

    public function calculateFrom(array $distances, Trackdata\Entity $trackdata, ?Route\Entity $route = null): void
    {
        $Calculator = new Calculator($this);
        $Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
        $Calculator->setDistances($distances);
        $Calculator->calculateFrom($trackdata, $route);
    }

    public function calculateFromTimes(array $times, Trackdata\Entity $trackdata, ?Route\Entity $route = null, ?Swimdata\Entity $swimdata = null): void
    {
        $Calculator = new Calculator($this);
        $Calculator->calculateAdditionalValues($this->CalculateAdditionalValues);
        $Calculator->setTimes($times);
        $Calculator->calculateFrom($trackdata, $route, ($swimdata != null && $swimdata->num() > 0 ? $swimdata : null));
    }

    public function readFrom(Splits\Entity $splits): void
    {
        $SplitsReader = new SplitsReader($this);
        $SplitsReader->readFrom($splits);
    }

    public function add(Lap $object): void
    {
        $this->Objects[] = $object;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function at(int $index): \Runalyze\Data\Laps\Lap
    {
        if ($index < 0 || $index > $this->num()) {
            throw new \InvalidArgumentException('Index out of bounds.');
        }

        return $this->Objects[$index];
    }

    /**
     * @return \Runalyze\Data\Laps\Lap[]
     */
    public function objects(): array
    {
        return $this->Objects;
    }

    public function num(): int
    {
        return count($this->Objects);
    }

    /**
     * true if there other laps with mode not ACTIVE and not RESTING.
     * #TSC
     */
    public function hasMoreThanActiveOrRestLaps(): bool
    {
        foreach ($this->Objects as $lap) {
            if (! $lap->getMode()->isActive() && ! $lap->getMode()->isRest()) {
                return true;
            }
        }

        return false;
    }
}
