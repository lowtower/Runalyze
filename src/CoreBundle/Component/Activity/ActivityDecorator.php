<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Util\LocalTime;

class ActivityDecorator
{
    public function __construct(
        protected ActivityContext $Context
    ) {
    }

    public function getTitle(bool $typeWithSport = false): string
    {
        if ($this->Context->getActivity()->getTitle() != '') {
            return sprintf('%s: %s', $this->Context->getSport()->getName(), $this->Context->getActivity()->getTitle());
        }

        if ($this->Context->getActivity()->getType() instanceof Type) {
            if ($typeWithSport) {
                return sprintf('%s: %s', $this->Context->getSport()->getName(), $this->Context->getActivity()->getType()->getName());
            }

            return $this->Context->getActivity()
                ->getType()
                ->getName();
        }

        return $this->Context->getSport()
            ->getName();
    }

    public function getDateTime(): LocalTime
    {
        return new LocalTime($this->Context->getActivity()->getTime());
    }

    /**
     * @return ?int [m]
     */
    public function getElevationUp(): ?int
    {
        return $this->getElevation();
    }

    /**
     * @return ?int [m]
     */
    public function getElevationDown(): ?int
    {
        return $this->getElevation(false);
    }

    protected function getElevation(bool $up = true): ?int
    {
        if ($this->Context->hasRoute() && ($this->Context->getRoute()->getElevationUp() > 0 || $this->Context->getRoute()->getElevationDown() > 0)) {
            return $up ? $this->Context->getRoute()
                ->getElevationUp() : $this->Context->getRoute()
                ->getElevationDown();
        }

        return $this->Context->getActivity()
            ->getElevation();
    }

    public function getEffectiveVO2max(RunalyzeConfigurationList $configurationList): float
    {
        return $configurationList->getVO2maxCorrectionFactor() * $this->getUncorrectedVO2max($configurationList);
    }

    public function getUncorrectedVO2max(RunalyzeConfigurationList $configurationList): ?float
    {
        if ($configurationList->useVO2maxCorrectionForElevation() && $this->Context->getActivity()->getVO2maxWithElevation() > 0.0) {
            return $this->Context->getActivity()
                ->getVO2maxWithElevation();
        }

        return $this->Context->getActivity()
            ->getVO2max();
    }

    /**
     * @return ?float [ms] can be negative for walking
     */
    public function getFlightTime(): ?float
    {
        $cadence = $this->Context->getActivity()
            ->getCadence();
        $groundContactTime = $this->Context->getActivity()
            ->getGroundcontact();

        if ($cadence > 0 && $groundContactTime > 0) {
            return 30000.0 / $cadence - $groundContactTime;
        }

        return null;
    }

    /**
     * @return ?float [%] can be negative for walking
     */
    public function getFlightRatio(): ?float
    {
        $cadence = $this->Context->getActivity()
            ->getCadence();
        $groundContactTime = $this->Context->getActivity()
            ->getGroundcontact();

        if ($cadence > 0 && $groundContactTime > 0) {
            return 1.0 - $cadence * $groundContactTime / 30000.0;
        }

        return null;
    }
}
