<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Calculation\Elevation\DistanceModifier;
use Runalyze\Calculation\JD\LegacyEffectiveVO2max;

class VO2maxCalculationDetailsDecorator
{
    public function __construct(
        protected ActivityContext $Context,
        protected RunalyzeConfigurationList $ConfigurationList
    ) {
    }

    /**
     * @return int [%]
     */
    public function getPercentVO2maxVelocityByHeartRate(): int
    {
        return (int) round(100.0 * LegacyEffectiveVO2max::percentageAt(
            $this->Context->getActivity()
                ->getPulseAvg() / $this->ConfigurationList->getMaximalHeartRate()
        ));
    }

    public function getCorrectedVO2max(): float
    {
        return $this->Context->getActivity()
            ->getVO2max() * $this->ConfigurationList->getVO2maxCorrectionFactor();
    }

    public function getCorrectedVO2maxWithElevationAdjustment(): float
    {
        return $this->Context->getActivity()
            ->getVO2maxWithElevation() * $this->ConfigurationList->getVO2maxCorrectionFactor();
    }

    public function getDistanceModifierForElevationAdjustment(): DistanceModifier
    {
        $modifier = new DistanceModifier(
            $this->Context->getActivity()
                ->getDistance(),
            $this->Context->getDecorator()
                ->getElevationUp(),
            $this->Context->getDecorator()
                ->getElevationDown()
        );
        $modifier->setCorrectionValues(
            $this->ConfigurationList->getVO2max()
                ->getAdditionalDistancePerPositiveElevation(),
            $this->ConfigurationList->getVO2max()
                ->getAdditionalDistancePerNegativeElevation()
        );

        return $modifier;
    }
}
