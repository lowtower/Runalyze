<?php

namespace Runalyze\Profile\Sport;

use Runalyze\Metrics\LegacyUnitConverter;

abstract class AbstractSport implements ProfileInterface
{
    /**
     * @var int enum from SportProfile
     */
    private $Enum;

    /**
     * @param int $enum from SportProfile
     */
    public function __construct($enum)
    {
        $this->Enum = $enum;
    }

    public function getInternalProfileEnum()
    {
        return $this->Enum;
    }

    /**
     * @return bool
     */
    final public function isCustom()
    {
        return $this->Enum == SportProfile::GENERIC;
    }

    /**
     * @return bool
     */
    final public function isRunning()
    {
        return $this->Enum == SportProfile::RUNNING;
    }

    /**
     * @return bool
     */
    final public function isCycling()
    {
        return $this->Enum == SportProfile::CYCLING || $this->Enum == SportProfile::E_MTB_MOUNTAIN;
    }

    /**
     * @return bool
     */
    final public function isSwimming()
    {
        return $this->Enum == SportProfile::SWIMMING;
    }

    /**
     * @return int
     * @see \Runalyze\Parameter\Application\PaceUnit
     */
    public function getLegacyPaceUnitEnum()
    {
        return (new LegacyUnitConverter())->getLegacyPaceUnit($this->getPaceUnitEnum(), true);
    }
}
