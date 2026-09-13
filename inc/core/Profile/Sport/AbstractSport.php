<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\LegacyUnitConverter;

abstract class AbstractSport implements ProfileInterface
{
    /**
     * @param int $Enum from SportProfile
     */
    public function __construct(
        private int $Enum
    ) {
    }

    public function getInternalProfileEnum(): int
    {
        return $this->Enum;
    }

    final public function isCustom(): bool
    {
        return $this->Enum === SportProfile::GENERIC;
    }

    final public function isRunning(): bool
    {
        return $this->Enum === SportProfile::RUNNING;
    }

    final public function isCycling(): bool
    {
        return $this->Enum === SportProfile::CYCLING || $this->Enum === SportProfile::E_MTB_MOUNTAIN;
    }

    final public function isSwimming(): bool
    {
        return $this->Enum === SportProfile::SWIMMING;
    }

    /**
     * @return \Runalyze\Activity\PaceUnit\AbstractUnit|int
     * @see \Runalyze\Parameter\Application\PaceUnit
     */
    public function getLegacyPaceUnitEnum()
    {
        return (new LegacyUnitConverter())->getLegacyPaceUnit($this->getPaceUnitEnum(), true);
    }
}
