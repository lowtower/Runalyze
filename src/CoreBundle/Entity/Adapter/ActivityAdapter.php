<?php

namespace Runalyze\Bundle\CoreBundle\Entity\Adapter;

use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbFinder;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbScoreCalculator;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\FlatOrHillyAnalyzer;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\NightDetector;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\PowerCalculator;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\TrimpCalculator;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\VO2maxCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Profile\FitSdk\StrokeTypeProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\ElevationCorrection\StepwiseElevationProfileFixer;
use Runalyze\Sports\ClimbQuantification\FietsIndex;
use Runalyze\Util\LocalTime;

class ActivityAdapter
{
    public function __construct(
        protected Training $Activity
    ) {
    }

    public function setAccountToRelatedEntities(): void
    {
        if ($this->Activity->hasRoute()) {
            $this->Activity->getRoute()
                ->setAccount($this->Activity->getAccount());
        }

        if ($this->Activity->hasTrackdata()) {
            $this->Activity->getTrackdata()
                ->setAccount($this->Activity->getAccount());
        }

        if ($this->Activity->hasSwimdata()) {
            $this->Activity->getSwimdata()
                ->setAccount($this->Activity->getAccount());
        }

        if ($this->Activity->hasHrv()) {
            $this->Activity->getHrv()
                ->setAccount($this->Activity->getAccount());
        }

        if ($this->Activity->hasRaceresult()) {
            $this->Activity->getRaceresult()
                ->setAccount($this->Activity->getAccount());
        }
    }

    public function getAgeOfActivity(): int
    {
        return LocalTime::now() - $this->Activity->getTime();
    }

    /**
     * @param int $days
     */
    public function isNotOlderThanXDays($days): bool
    {
        return (new LocalTime())->diff(new LocalTime($this->Activity->getTime()))
            ->days <= $days;
    }

    public function isRunning(): bool
    {
        return $this->Activity->hasSport()
            && $this->Activity->getSport()
                ->getInternalSport()
                ->isRunning();
    }

    public function isCycling(): bool
    {
        return $this->Activity->hasSport()
            && $this->Activity->getSport()
                ->getInternalSport()
                ->isCycling();
    }

    public function isSwimming(): bool
    {
        return $this->Activity->hasSport()
            && $this->Activity->getSport()
                ->getInternalSport()
                ->isSwimming();
    }

    /**
     * @return int [bpm]
     */
    public function getAverageHeartRateWithFallbackToTypeOrSport(): int
    {
        if ($this->Activity->getPulseAvg() > 0) {
            return $this->Activity->getPulseAvg();
        }

        if ($this->Activity->getType() instanceof Type) {
            return $this->Activity->getType()
                ->getHrAvg();
        }

        if ($this->Activity->hasSport()) {
            return $this->Activity->getSport()
                ->getHfavg();
        }

        return 0;
    }

    public function setActivityIdIfEmpty(): void
    {
        if ($this->Activity->getActivityId() === null) {
            $this->Activity->setActivityId((int) floor($this->Activity->getTime() / 60.0) * 60);
        }
    }

    public function updateSimpleCalculatedValues(): void
    {
        $this->calculateStrideLength();
        $this->calculateVerticalRatio();
    }

    protected function calculateStrideLength()
    {
        $strideLength = null;

        if ($this->isRunning() && $this->Activity->getS() > 0 && $this->Activity->getCadence() > 0 && $this->Activity->getDistance() > 0) {
            $strideLength = (int) round($this->Activity->getDistance() * 1000.0 * 100.0 / ($this->Activity->getCadence() * 2.0 / 60.0 * $this->Activity->getS()));

            if ($strideLength > 255) {
                $strideLength = null;
            }
        }

        $this->Activity->setStrideLength($strideLength);
    }

    protected function calculateVerticalRatio()
    {
        $verticalRatio = null;

        if ($this->Activity->getVerticalOscillation() > 0 && $this->Activity->getStrideLength() > 0) {
            $verticalRatio = (int) round(100.0 * $this->Activity->getVerticalOscillation() / $this->Activity->getStrideLength());
        }

        $this->Activity->setVerticalRatio($verticalRatio);
    }

    public function isRelevantForCurrentMarathonShape(int $numberOfDaysToConsiderForShape): bool
    {
        return
            $this->isRunning() &&
            $this->isNotOlderThanXDays($numberOfDaysToConsiderForShape)
        ;
    }

    public function isRelevantForCurrentEffectiveVO2maxShape(int $numberOfDaysToConsiderForShape): bool
    {
        return
            $this->isRunning() &&
            $this->Activity->getUseVO2max() &&
            $this->Activity->getVO2max() > 0.0 &&
            $this->isNotOlderThanXDays($numberOfDaysToConsiderForShape)
        ;
    }

    public function calculateEnergyConsumptionIfEmpty(): void
    {
        if (($this->Activity->getKcal() === null || $this->Activity->getKcal() === 0) && $this->Activity->hasSport()) {
            $energyConsumption = $this->Activity->getSport()
                ->getKcal() * $this->Activity->getS() / 3600.0;

            $this->Activity->setKcal($energyConsumption > 0 ? (int) $energyConsumption : null);
        }
    }

    /**
     * @param int $gender enum, see \Runalyze\Profile\Athlete\Gender
     * @param int $heartRateMaximum [bpm]
     * @param int $heartRateResting [bpm]
     */
    public function calculateTrimp(int $gender, int $heartRateMaximum, int $heartRateResting): void
    {
        $calculator = new TrimpCalculator();
        $calculator->calculateFor($this->Activity, $gender, $heartRateMaximum, $heartRateResting);
    }

    /**
     * @param int $heartRateMaximum [bpm]
     * @param int $correctionForPositiveElevation [m]
     * @param int $correctionForNegativeElevation [m]
     */
    public function calculateEffectiveVO2max(int $heartRateMaximum, int $correctionForPositiveElevation, int $correctionForNegativeElevation): void
    {
        $calculator = new VO2maxCalculator();
        $calculator->calculateFor($this->Activity, $heartRateMaximum, $correctionForPositiveElevation, $correctionForNegativeElevation);
    }

    public function removeEffectiveVO2max(): void
    {
        $this->Activity->setVO2maxByTime(null);
        $this->Activity->setVO2max(null);
        $this->Activity->setVO2maxWithElevation(null);
    }

    /**
     * @param ?float $athleteWeight [kg]
     * @param ?float $bikeWeight [kg]
     */
    public function calculatePower(?float $athleteWeight = null, ?float $bikeWeight = null): void
    {
        $calculator = new PowerCalculator();
        $calculator->calculateFor($this->Activity);
    }

    public function removePower(): void
    {
        $this->Activity->setPowerCalculated(null);
        $this->Activity->setPower(null);

        if ($this->Activity->hasTrackdata()) {
            $this->Activity->getTrackdata()
                ->setPower(null);
        }
    }

    public function removeWeather(): void
    {
        $this->Activity->setWeatherid(WeatherConditionProfile::UNKNOWN);
        $this->Activity->setWeatherSource(null);
        // #TSC do not remove temperature for indoor sports
        // $this->Activity->setTemperature(null);
        $this->Activity->setWindSpeed(null);
        $this->Activity->setWindDeg(null);
        $this->Activity->setPressure(null);
        $this->Activity->setHumidity(null);
    }

    public function calculateIfActivityWasAtNight(): void
    {
        if ($this->Activity->getRoute() instanceof Route && $this->Activity->getRoute()->hasGeohashes()) {
            $this->Activity->setNight((new NightDetector())->isActivityAtNight($this->Activity));
        } else {
            $this->Activity->setNight(null);
        }
    }

    public function calculateValuesForSwimming(): void
    {
        $this->calculateTotalNumberOfStrokes();
        $this->calculateSwolf();
    }

    public function calculateTotalNumberOfStrokes(): void
    {
        if ($this->Activity->getSwimdata() instanceof Swimdata && $this->Activity->getSwimdata()->hasStrokes()) {
            $this->Activity->setTotalStrokes(array_sum($this->Activity->getSwimdata()->getStroke()));
        } else {
            $this->Activity->setTotalStrokes(null);
        }
    }

    public function calculateSwolf(): void
    {
        $swolf = null;
        if ($this->Activity->getTotalStrokes() !== null) {
            $totalTime = 0;
            $numberOfPoints = 0;

            /*
             * Preferred calculation:
             * Use the actual swimming time and ignore break/rest lanes.
             */
            if ($this->Activity->getSwimdata() instanceof Swimdata &&
                $this->Activity->getSwimdata()
                    ->hasStrokeTypes()
            ) {
                $totalTime = $this->Activity->getS();
                $numberOfPoints = count(array_filter($this->Activity->getSwimdata()->getStroketype(), fn ($strokeType): bool => $strokeType != StrokeTypeProfile::BREAK));
            } elseif ($this->Activity->getTrackdata() instanceof Trackdata && $this->Activity->getTrackdata()->hasTime()) {
                /*
                 * Fallback for swimming data without stroke types:
                 * Use the overall time and number of track-data points.
                 */
                $trackDataTime = $this->Activity->getTrackdata()
                    ->getTime();
                if (! empty($trackDataTime)) {
                    $totalTime = end($trackDataTime);
                    $numberOfPoints = count($trackDataTime);
                }
            }

            if ($totalTime > 0 && $numberOfPoints > 0) {
                $swolf = (int) round(($this->Activity->getTotalStrokes() + $totalTime) / $numberOfPoints);
            }
        }

        $this->Activity->setSwolf($swolf);
    }

    public function useElevationFromRoute(): void
    {
        if ($this->Activity->hasRoute()) {
            $this->Activity->setElevation($this->Activity->getRoute()->getElevation());
        } else {
            $this->Activity->setElevation(null);
        }
    }

    public function calculateClimbScore(): void
    {
        if ($this->canCalculateClimbScore()) {
            $elevationsBackup = $this->temporarilyFixStepwiseElevationsInRoute();

            (new FlatOrHillyAnalyzer())->calculatePercentageHillyFor($this->Activity);
            (new ClimbScoreCalculator(new FietsIndex(), new ClimbFinder()))->calculateFor($this->Activity);

            $this->revertTemporaryFixForStepwiseElevationsInRoute($elevationsBackup);
        } else {
            $this->Activity->setPercentageHilly(null);
            $this->Activity->setClimbScore(null);
        }
    }

    protected function canCalculateClimbScore(): bool
    {
        return
            $this->Activity->getRoute() instanceof Route &&
            $this->Activity->getRoute()
                ->hasElevations() &&
            $this->Activity->getTrackdata() instanceof Trackdata &&
            $this->Activity->getTrackdata()
                ->hasDistance()
        ;
    }

    protected function temporarilyFixStepwiseElevationsInRoute(): ?array
    {
        $backupElevations = $this->Activity->getRoute()
            ->getElevationsCorrected();

        if ($backupElevations !== null) {
            $this->Activity->getRoute()
                ->setElevationsCorrected(
                    (new StepwiseElevationProfileFixer(5, StepwiseElevationProfileFixer::METHOD_VARIABLE_GROUP_SIZE))
                        ->fixStepwiseElevations(
                            $this->Activity->getRoute()
                                ->getElevationsCorrected(),
                            $this->Activity->getTrackdata()
                                ->getDistance()
                        )
                );
        }

        return $backupElevations;
    }

    /**
     * @param array|null $backupElevations [m]
     */
    protected function revertTemporaryFixForStepwiseElevationsInRoute(?array $backupElevations = null)
    {
        $this->Activity->getRoute()
            ->setElevationsCorrected($backupElevations);
    }

    public function guessTimezoneBasedOnCoordinates(TimezoneLookup $timezoneLookup): void
    {
        if (
            $this->Activity->getTimezoneOffset() === null &&
            $this->Activity->hasRoute() &&
            $this->Activity->getRoute()
                ->hasGeohashes() &&
            $timezoneLookup->isPossible()
        ) {
            $this->Activity->getRoute()
                ->setStartEndGeohashes();

            $startPoint = $this->Activity->getRoute()
                ->getStartpoint();

            if ($startPoint !== null) {
                /** @var Geohash $geohashObj */
                $geohashObj = (new Geohash())->decode($startPoint);
                $coordinate = $geohashObj->getCoordinate();
                $timezone = $timezoneLookup->getTimezoneForCoordinate((float) $coordinate->getLongitude(), (float) $coordinate->getLatitude());
                $this->updateTimezoneForActivity($timezone);
            }
        }
    }

    /**
     * @param string|null $timezone
     */
    protected function updateTimezoneForActivity($timezone)
    {
        if ($timezone === null) {
            return;
        }

        $newOffset = (new \DateTime('', new \DateTimeZone($timezone)))->setTimestamp($this->Activity->getTime())
            ->getOffset() / 60;

        if ($this->Activity->getTimezoneOffset() !== null) {
            $this->Activity->setTime($this->Activity->getTime() + 60 * ($newOffset - $this->Activity->getTimezoneOffset()));
        }

        $this->Activity->setTimezoneOffset($newOffset);
    }
}
