<?php

namespace Runalyze\Calculation\Activity;

use Runalyze\Calculation\Elevation;
use Runalyze\Calculation\JD;
use Runalyze\Calculation\Trimp;
use Runalyze\Configuration;
use Runalyze\Context;
use Runalyze\Model;

class Calculator
{
    /**
     * @var Model\Activity\Entity
     */
    protected $Activity;

    /**
     * @var Model\Trackdata\Entity
     */
    protected $Trackdata;

    /**
     * @var Model\Route\Entity
     */
    protected $Route;

    public function __construct(
        Model\Activity\Entity $activity,
        ?Model\Trackdata\Entity $trackdata = null,
        ?Model\Route\Entity $route = null
    ) {
        $this->Activity = $activity;
        $this->Trackdata = $trackdata;
        $this->Route = $route;
    }

    protected function knowsTrackdata(): bool
    {
        return $this->Trackdata !== null;
    }

    protected function knowsRoute(): bool
    {
        return $this->Route !== null;
    }

    /**
     * @return float [ml/kg/min]
     */
    public function estimateVO2maxByTime(): string
    {
        $VO2max = new JD\LegacyEffectiveVO2max();
        $VO2max->fromPace($this->Activity->distance(), $this->Activity->duration());

        return $VO2max->uncorrectedValue();
    }

    /**
     * @param float|null $distance [km]
     * @return float [ml/kg/min]
     */
    public function estimateVO2maxByHeartRate($distance = null): string
    {
        if ($distance === null) {
            $distance = $this->Activity->distance();
        }

        $VO2max = new JD\LegacyEffectiveVO2max();
        $VO2max->fromPaceAndHR(
            $distance,
            $this->Activity->duration(),
            $this->Activity->hrAvg() / Configuration::Data()->HRmax()
        );

        return $VO2max->value();
    }

    /**
     * @return float [ml/kg/min]
     */
    public function estimateVO2maxByHeartRateWithElevation(): string
    {
        if ($this->knowsRoute()) {
            if ($this->Route->elevationUp() > 0 || $this->Route->elevationDown() > 0) {
                return $this->estimateVO2maxByHeartRateWithElevationFor($this->Route->elevationUp(), $this->Route->elevationDown());
            }

            return $this->estimateVO2maxByHeartRateWithElevationFor($this->Route->elevation(), $this->Route->elevation());
        }

        return $this->estimateVO2maxByHeartRateWithElevationFor($this->Activity->elevation(), $this->Activity->elevation());
    }

    /**
     * @param int $up
     * @param int $down
     */
    public function estimateVO2maxByHeartRateWithElevationFor($up, $down): string
    {
        $Modifier = new Elevation\DistanceModifier(
            $this->Activity->distance(),
            $up,
            $down,
            Configuration::VO2max()
        );

        return $this->estimateVO2maxByHeartRate($Modifier->correctedDistance());
    }

    public function calculateTrimp(): float
    {
        if ($this->knowsTrackdata() && $this->Trackdata->has(Model\Trackdata\Entity::HEARTRATE)) {
            $Collector = new Trimp\DataCollector($this->Trackdata->heartRate(), $this->Trackdata->time());
            $data = $Collector->result();
        } elseif ($this->Activity->hrAvg() > 0) {
            $data = [
                $this->Activity->hrAvg() => $this->Activity->duration(),
            ];
        } else {
            $Factory = Context::Factory();

            if ($this->Activity->typeid() > 0) {
                $data = [
                    $Factory->type((int) $this->Activity->typeid())
                        ->hrAvg() => $this->Activity->duration(),
                ];
            } else {
                $data = [
                    $Factory->sport((int) $this->Activity->sportid())
                        ->avgHR() => $this->Activity->duration(),
                ];
            }
        }

        $Athlete = Context::Athlete();
        $Calculator = new Trimp\Calculator($Athlete, $data);

        return round($Calculator->value());
    }
}
