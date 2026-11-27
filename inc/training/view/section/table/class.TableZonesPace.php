<?php

declare(strict_types=1);

/**
 * This file contains class::TableZonesPace
 * @package Runalyze\DataObjects\Training\View
 */

use Runalyze\Activity\HeartRate;
use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Calculation\Activity\PaceSmoother;
use Runalyze\Calculation\Distribution\TimeSeriesForTrackdata;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Model\Trackdata;

/**
 * Display pace zones
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View
 */
class TableZonesPace extends TableZonesAbstract
{
    /**
     * Step size to smooth pace date
     */
    public const int STEP_SIZE = 10;

    protected AbstractPaceUnit $paceUnit;

    /**
     * Get title for average
     */
    public function titleForAverage(): string
    {
        return __('avg.') . '&nbsp;' . __('HR');
    }

    protected function initData()
    {
        $this->paceUnit = $this->Context->sport()->getSpeedUnit();

        $Zones = $this->computeZones();
        $paceFormatter = new PaceFormatter($this->paceUnit);

        $HR = new HeartRate(0, Runalyze\Context::Athlete());

        foreach ($Zones as $paceInSeconds => $Info) {
            if ($Info['time'] > parent::MINIMUM_TIME_IN_ZONE) {
                $HR->setBPM($Info['time'] > 0 ? $Info['hr'] / $Info['time'] : 0);

                $this->Data[] = [
                    'zone' => $paceInSeconds == 0 ? __('faster') : '&gt; ' . $paceFormatter->format($paceInSeconds),
                    'time' => $Info['time'],
                    'distance' => $Info['distance'],
                    'average' => $HR->asBPM() > 0 ? $HR->string() : '-',
                ];
            }
        }
    }

    protected function computeZones(): array
    {
        // TODO
        // - move this a calculation class
        // - make zones configurable
        $Zones = [];
        $SmoothTrackdata = clone $this->Context->trackdata();
        $Smoother = new PaceSmoother($SmoothTrackdata, true);
        $SmoothTrackdata->set(Trackdata\Entity::PACE, $Smoother->smooth(self::STEP_SIZE, PaceSmoother::MODE_STEP));
        $hasHR = $this->Context->trackdata()
            ->has(Trackdata\Entity::HEARTRATE);

        $Distribution = new TimeSeriesForTrackdata(
            $SmoothTrackdata,
            Trackdata\Entity::PACE,
            [Trackdata\Entity::DISTANCE],
            $hasHR ? [Trackdata\Entity::HEARTRATE] : []
        );
        $Data = $Distribution->data();

        foreach ($Distribution->histogram() as $paceInSeconds => $seconds) {
            $pace = $this->zoneFor($paceInSeconds);

            if (! isset($Zones[$pace])) {
                $Zones[$pace] = [
                    'time' => $seconds,
                    'distance' => $Data[$paceInSeconds][Trackdata\Entity::DISTANCE],
                    'hr' => $hasHR ? $Data[$paceInSeconds][Trackdata\Entity::HEARTRATE] * $seconds : 0,
                ];
            } else {
                $Zones[$pace]['time'] += $seconds;
                $Zones[$pace]['distance'] += $Data[$paceInSeconds][Trackdata\Entity::DISTANCE];
                $Zones[$pace]['hr'] += $hasHR ? $Data[$paceInSeconds][Trackdata\Entity::HEARTRATE] * $seconds : 0;
            }
        }

        ksort($Zones, SORT_NUMERIC);

        return $Zones;
    }

    protected function zoneFor(int $paceInSeconds): int
    {
        if ($paceInSeconds === 0) {
            return 0;
        }

        $value = $this->paceUnit->fromBaseUnit($paceInSeconds);
        if ($this->paceUnit instanceof AbstractPaceInTimeFormatUnit) {
            return (int) round($this->paceUnit->toBaseUnit(30 * floor($value / 30)));
        }

        return $value < 1.0 ? 0 : (int) $this->paceUnit->toBaseUnit(floor($value));
    }
}
