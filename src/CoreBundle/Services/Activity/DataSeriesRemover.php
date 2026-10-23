<?php

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class DataSeriesRemover
{
    public const string KEY_TRACKDATA_TIME = 'time';

    public const string KEY_TRACKDATA_DISTANCE = 'distance';

    public const string KEY_TRACKDATA_HEART_RATE = 'heartRate';

    public const string KEY_TRACKDATA_CADENCE = 'cadence';

    public const string KEY_TRACKDATA_POWER = 'power';

    public const string KEY_TRACKDATA_VERTICAL_OSCILLATION = 'verticalOscillation';

    public const string KEY_TRACKDATA_GROUND_CONTACT_TIME = 'groundContactTime';

    public const string KEY_TRACKDATA_GROUND_CONTACT_BALANCE = 'groundContactBalance';

    public const string KEY_TRACKDATA_IMPACT_GS_LEFT = 'impact_gs_left';

    public const string KEY_TRACKDATA_IMPACT_GS_RIGHT = 'impact_gs_right';

    public const string KEY_TRACKDATA_BRAKING_GS_LEFT = 'braking_gs_left';

    public const string KEY_TRACKDATA_BRAKING_GS_RIGHT = 'braking_gs_right';

    public const string KEY_TRACKDATA_FOOTSTRIKE_TYPE_LEFT = 'footstrike_type_left';

    public const string KEY_TRACKDATA_FOOTSTRIKE_TYPE_RIGHT = 'footstrike_type_right';

    public const string KEY_TRACKDATA_PRONATION_EXCURSION_LEFT = 'pronation_excursion_left';

    public const string KEY_TRACKDATA_PRONATION_EXCURSION_RIGHT = 'pronation_excursion_right';

    public const string KEY_TRACKDATA_PERFOMANCE_CONDITION = 'performance_condition';

    public const string KEY_TRACKDATA_RESPIRATION_RATE = 'respiration_rate';

    public const string KEY_TRACKDATA_MUSCLE_OXYGENATION = 'smo2';

    public const string KEY_TRACKDATA_MUSCLE_OXYGENATION_2 = 'smo2_2';

    public const string KEY_TRACKDATA_TOTAL_HAEMOGLOBIN = 'thb';

    public const string KEY_TRACKDATA_TOTAL_HAEMOGLOBIN_2 = 'thb_2';

    public const string KEY_TRACKDATA_TEMPERATURE = 'temperature';

    public const string KEY_ROUTE_GEOHASHES = 'geohashes';

    public const string KEY_ROUTE_ELEVATION_ORIGINAL = 'elevationOriginal';

    public const string KEY_ROUTE_ELEVATION_CORRECTED = 'elevationCorrected';

    public const string KEY_HRV = 'hrv';

    public function __construct(
        protected \Doctrine\ORM\EntityManager $EntityManager
    ) {}

    /**
     * @param mixed $formData
     */
    public function handleRequest($formData, Training $activity): void
    {
        if (! is_array($formData) || array_filter($formData, fn ($v): bool => ! is_string($v) && ! is_int($v))) {
            return;
        }

        $keys = array_flip($formData);

        $this->handleTrackDataKeys($keys, $activity);
        $this->handleRouteKeys($keys, $activity);
        $this->handleHrvKeys($keys, $activity);
    }

    /**
     * @param int[]|string[] $keys
     */
    protected function handleTrackDataKeys(array $keys, Training $activity)
    {
        if (! $activity->hasTrackdata()) {
            return;
        }

        $trackData = $activity->getTrackdata();

        if (isset($keys[self::KEY_TRACKDATA_TIME]) && $trackData->hasTime()) {
            $trackData->setTime(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_DISTANCE]) && $trackData->hasDistance()) {
            $trackData->setDistance(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_HEART_RATE]) && $trackData->hasHeartrate()) {
            $trackData->setHeartrate(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_CADENCE]) && $trackData->hasCadence()) {
            $trackData->setCadence(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_POWER]) && $trackData->hasPower()) {
            $trackData->setPower(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_VERTICAL_OSCILLATION]) && $trackData->hasVerticalOscillation()) {
            $trackData->setVerticalOscillation(null);
            $activity->setVerticalOscillation(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_GROUND_CONTACT_TIME]) && $trackData->hasGroundcontact()) {
            $trackData->setGroundcontact(null);
            $activity->setGroundcontact(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_GROUND_CONTACT_BALANCE]) && $trackData->hasGroundcontactBalance()) {
            $trackData->setGroundcontactBalance(null);
            $activity->setGroundcontactBalance(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_MUSCLE_OXYGENATION]) && $trackData->hasSmo20()) {
            $trackData->setSmo20(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_MUSCLE_OXYGENATION_2]) && $trackData->hasSmo21()) {
            $trackData->setSmo21(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN]) && $trackData->hasThb0()) {
            $trackData->setThb0(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN_2]) && $trackData->hasThb1()) {
            $trackData->setThb1(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_TEMPERATURE]) && $trackData->hasTemperature()) {
            $trackData->setTemperature(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_PERFOMANCE_CONDITION]) && $trackData->hasPerformanceCondition()) {
            $trackData->setPerformanceCondition(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_RESPIRATION_RATE]) && $trackData->hasRespirationRate()) {
            $trackData->setRespirationRate(null);
        }

        $this->handleTrackDataForRunScribeKeys($keys, $activity);

        if ($trackData->isEmpty()) {
            $this->EntityManager->remove($trackData);

            $activity->setTrackdata();
        }
    }

    /**
     * @param array<string, mixed> $keys
     */
    protected function handleTrackDataForRunScribeKeys(array $keys, Training $activity)
    {
        $trackData = $activity->getTrackdata();

        if (isset($keys[self::KEY_TRACKDATA_IMPACT_GS_LEFT]) && $trackData->hasImpactGsLeft()) {
            $trackData->setImpactGsLeft(null);
            $activity->setAvgImpactGsLeft(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_IMPACT_GS_RIGHT]) && $trackData->hasImpactGsRight()) {
            $trackData->setImpactGsRight(null);
            $activity->setAvgImpactGsRight(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_BRAKING_GS_LEFT]) && $trackData->hasBrakingGsLeft()) {
            $trackData->setBrakingGsLeft(null);
            $activity->setAvgBrakingGsLeft(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_BRAKING_GS_RIGHT]) && $trackData->hasBrakingGsRight()) {
            $trackData->setBrakingGsRight(null);
            $activity->setAvgBrakingGsRight(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_FOOTSTRIKE_TYPE_LEFT]) && $trackData->hasFootstrikeTypeLeft()) {
            $trackData->setFootstrikeTypeLeft(null);
            $activity->setAvgFootstrikeTypeLeft(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_FOOTSTRIKE_TYPE_RIGHT]) && $trackData->hasFootstrikeTypeRight()) {
            $trackData->setFootstrikeTypeRight(null);
            $activity->setAvgFootstrikeTypeRight(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_PRONATION_EXCURSION_LEFT]) && $trackData->hasPronationExcursionLeft()) {
            $trackData->setPronationExcursionLeft(null);
            $activity->setAvgPronationExcursionLeft(null);
        }

        if (isset($keys[self::KEY_TRACKDATA_PRONATION_EXCURSION_RIGHT]) && $trackData->hasPronationExcursionRight()) {
            $trackData->setPronationExcursionRight(null);
            $activity->setAvgPronationExcursionRight(null);
        }
    }

    /**
     * @param int[]|string[] $keys
     */
    protected function handleRouteKeys(array $keys, Training $activity)
    {
        if (! $activity->hasRoute()) {
            return;
        }

        $route = $activity->getRoute();

        if (isset($keys[self::KEY_ROUTE_GEOHASHES]) && $route->hasGeohashes()) {
            $route->setGeohashes(null);
        }

        if (isset($keys[self::KEY_ROUTE_ELEVATION_ORIGINAL]) && $route->hasOriginalElevations()) {
            $route->setElevationsOriginal(null);
        }

        if (isset($keys[self::KEY_ROUTE_ELEVATION_CORRECTED]) && $route->hasCorrectedElevations()) {
            $route->setElevationsCorrected(null);
        }

        if ($route->isEmpty()) {
            $this->EntityManager->remove($route);

            $activity->setRoute();
            $activity->setElevation(null);
        }
    }

    /**
     * @param int[]|string[] $keys
     */
    protected function handleHrvKeys(array $keys, Training $activity)
    {
        if (isset($keys[self::KEY_HRV]) && $activity->hasHrv()) {
            $this->EntityManager->remove($activity->getHrv());

            $activity->setHrv();
        }
    }

    public static function getChoicesForActivity(Training $activity): array
    {
        return array_merge(
            self::getChoicesForTrackData($activity->getTrackdata()),
            self::getChoicesForTrackDataForRunScribe($activity->getTrackdata()),
            self::getChoicesForRoute($activity->getRoute()),
            self::getChoicesForHrv($activity->getHrv())
        );
    }

    private static function getChoicesForTrackData(?Trackdata $trackData = null): array
    {
        $choices = [];

        if (! $trackData instanceof Trackdata) {
            return [];
        }

        if ($trackData->hasTime()) {
            $choices['Time'] = self::KEY_TRACKDATA_TIME;
        }

        if ($trackData->hasDistance()) {
            $choices['Distance'] = self::KEY_TRACKDATA_DISTANCE;
        }

        if ($trackData->hasHeartrate()) {
            $choices['Heart rate'] = self::KEY_TRACKDATA_HEART_RATE;
        }

        if ($trackData->hasCadence()) {
            $choices['Cadence'] = self::KEY_TRACKDATA_CADENCE;
        }

        if ($trackData->hasPower()) {
            $choices['Power'] = self::KEY_TRACKDATA_POWER;
        }

        if ($trackData->hasVerticalOscillation()) {
            $choices['Vertical oscillation'] = self::KEY_TRACKDATA_VERTICAL_OSCILLATION;
        }

        if ($trackData->hasGroundcontact()) {
            $choices['Ground contact time'] = self::KEY_TRACKDATA_GROUND_CONTACT_TIME;
        }

        if ($trackData->hasGroundcontactBalance()) {
            $choices['Ground contact time balance'] = self::KEY_TRACKDATA_GROUND_CONTACT_BALANCE;
        }

        if ($trackData->hasSmo20()) {
            $choices['SmO2'] = self::KEY_TRACKDATA_MUSCLE_OXYGENATION;
        }

        if ($trackData->hasSmo21()) {
            $choices['SmO2 (2)'] = self::KEY_TRACKDATA_MUSCLE_OXYGENATION_2;
        }

        if ($trackData->hasThb0()) {
            $choices['THb'] = self::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN;
        }

        if ($trackData->hasThb1()) {
            $choices['THb (2)'] = self::KEY_TRACKDATA_TOTAL_HAEMOGLOBIN_2;
        }

        if ($trackData->hasTemperature()) {
            $choices['Temperature'] = self::KEY_TRACKDATA_TEMPERATURE;
        }

        if ($trackData->hasPerformanceCondition()) {
            $choices['Leistungszustand'] = self::KEY_TRACKDATA_PERFOMANCE_CONDITION;
        }

        if ($trackData->hasRespirationRate()) {
            $choices['Atemfrequenz'] = self::KEY_TRACKDATA_RESPIRATION_RATE;
        }

        return $choices;
    }

    private static function getChoicesForTrackDataForRunScribe(?Trackdata $trackData = null): array
    {
        $choices = [];

        if (! $trackData instanceof Trackdata) {
            return [];
        }

        if ($trackData->hasImpactGsLeft()) {
            $choices['Impact Gs (left)'] = self::KEY_TRACKDATA_IMPACT_GS_LEFT;
        }

        if ($trackData->hasImpactGsRight()) {
            $choices['Impact Gs (right)'] = self::KEY_TRACKDATA_IMPACT_GS_RIGHT;
        }

        if ($trackData->hasBrakingGsLeft()) {
            $choices['Braking Gs (left)'] = self::KEY_TRACKDATA_BRAKING_GS_LEFT;
        }

        if ($trackData->hasBrakingGsRight()) {
            $choices['Braking Gs (right)'] = self::KEY_TRACKDATA_BRAKING_GS_RIGHT;
        }

        if ($trackData->hasFootstrikeTypeLeft()) {
            $choices['Footstrike type (left)'] = self::KEY_TRACKDATA_FOOTSTRIKE_TYPE_LEFT;
        }

        if ($trackData->hasFootstrikeTypeRight()) {
            $choices['Footstrike type (right)'] = self::KEY_TRACKDATA_FOOTSTRIKE_TYPE_RIGHT;
        }

        if ($trackData->hasPronationExcursionLeft()) {
            $choices['Pronation excursion (left)'] = self::KEY_TRACKDATA_PRONATION_EXCURSION_LEFT;
        }

        if ($trackData->hasPronationExcursionRight()) {
            $choices['Pronation excursion (right)'] = self::KEY_TRACKDATA_PRONATION_EXCURSION_RIGHT;
        }

        return $choices;
    }

    private static function getChoicesForRoute(?Route $route = null): array
    {
        $choices = [];

        if (! $route instanceof Route) {
            return [];
        }

        if ($route->hasGeohashes()) {
            $choices['GPS path'] = self::KEY_ROUTE_GEOHASHES;
        }

        if ($route->hasOriginalElevations()) {
            $choices['Original elevation'] = self::KEY_ROUTE_ELEVATION_ORIGINAL;
        }

        if ($route->hasCorrectedElevations()) {
            $choices['Corrected elevation'] = self::KEY_ROUTE_ELEVATION_CORRECTED;
        }

        return $choices;
    }

    private static function getChoicesForHrv(?Hrv $hrv = null): array
    {
        if (! $hrv instanceof Hrv) {
            return [];
        }

        return [
            'HRV' => self::KEY_HRV,
        ];
    }
}
