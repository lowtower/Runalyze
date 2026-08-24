<?php

declare(strict_types=1);

namespace Runalyze\Profile\Sport\Mapping;

use Runalyze\Profile\FitSdk;
use Runalyze\Profile\Mapping\AbstractMapping;
use Runalyze\Profile\Sport\SportProfile;

class FitSdkMapping extends AbstractMapping
{
    /**
     * @return array [fitSdkId => runalyzeId, ...]
     */
    protected function getMapping(): array
    {
        return [
            FitSdk\SportProfile::GENERIC => SportProfile::GENERIC,
            FitSdk\SportProfile::RUNNING => SportProfile::RUNNING,
            FitSdk\SportProfile::E_BIKING => SportProfile::CYCLING,
            FitSdk\SportProfile::CYCLING => SportProfile::CYCLING,
            FitSdk\SportProfile::SWIMMING => SportProfile::SWIMMING,
            FitSdk\SportProfile::ROWING => SportProfile::ROWING,
            FitSdk\SportProfile::WALKING => SportProfile::HIKING,
            FitSdk\SportProfile::HIKING => SportProfile::HIKING,
            FitSdk\SportProfile::MOUNTAINEERING => SportProfile::MOUNTAINEERING,
            FitSdk\SportProfile::SNOWSHOEING => SportProfile::SNOW_SHOEING,
            FitSdk\SportProfile::CROSS_COUNTRY_SKIING => SportProfile::CROSS_COUNTRY_SKIING,
            FitSdk\SportProfile::CLIMBING_INDOOR => SportProfile::CLIMBING_INDOOR,
            FitSdk\SportProfile::BOULDERING => SportProfile::BOULDERING,
            FitSdk\SportProfile::STRENGTH_TRAINING => SportProfile::STRENGTH_TRAINING,
            FitSdk\SportProfile::E_MTB_MOUNTAIN => SportProfile::E_MTB_MOUNTAIN,
            FitSdk\SportProfile::CARDIO => SportProfile::HIIT_CARDIO,
            FitSdk\SportProfile::HIIT => SportProfile::HIIT_CARDIO,
        ];
    }

    protected function internalDefault(): int
    {
        return SportProfile::GENERIC;
    }

    protected function externalDefault(): int
    {
        return FitSdk\SportProfile::GENERIC;
    }

    /**
     * Returns an internal sport ID for a FIT sport/sub-sport combination.
     *
     * @param int|string $sport
     * @param int|string $subsport
     *
     * @return int|null
     */
    public function sportWithSubsportToInternal($sport, $subsport)
    {
        $id = (int) $sport * 1000 + (int) $subsport;
        $mapping = $this->getMapping();

        return $mapping[$id] ?? null;
    }
}
