<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector;
use Runalyze\Model;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Util\LocalTime;

class RaceAnalysis
{
    /**
     * @var float
     */
    protected $VO2maxFactor;

    /**
     * @var float
     */
    protected $VO2maxShape;

    /**
     * @var Model\Activity\Entity
     */
    protected $Activity;

    /**
     * @param float $vo2maxFactor
     * @param float $vo2maxShape
     */
    public function __construct(Model\Activity\Entity $activity, $vo2maxFactor, $vo2maxShape)
    {
        $this->Activity = $activity;
        $this->VO2maxFactor = $vo2maxFactor;
        $this->VO2maxShape = $vo2maxShape;
    }

    /**
     * @TODO use Twig extension for LocalTime
     */
    public function getDate(): string
    {
        return LocalTime::date('d.m.Y', $this->Activity->timestamp());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Activity->title();
    }

    public function getDistance(): string
    {
        $distance = new Distance($this->Activity->distance());

        if ($this->Activity->isTrack()) {
            return $distance->stringMeter();
        }

        return $distance->string();
    }

    public function getDuration(): string
    {
        return $this->formatTime($this->Activity->duration());
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getVO2maxByTime()
    {
        return $this->Activity->vo2maxByTime();
    }

    /**
     * @return int [bpm]
     */
    public function getHeartRateInBpm()
    {
        return $this->Activity->hrAvg();
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getVO2maxByHeartRate()
    {
        return $this->Activity->vo2maxByHeartRate();
    }

    public function getPrognosisByHeartRate(): string
    {
        return $this->prognosisFor($this->Activity->vo2maxByHeartRate(), $this->Activity->distance());
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getVO2maxByHeartRateAfterCorrection()
    {
        return $this->VO2maxFactor * $this->Activity->vo2maxByHeartRate();
    }

    public function getPrognosisByHeartRateAfterCorrection(): string
    {
        return $this->prognosisFor($this->VO2maxFactor * $this->Activity->vo2maxByHeartRate(), $this->Activity->distance());
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getVO2maxByShape()
    {
        return $this->VO2maxShape;
    }

    public function getPrognosisByShape(): string
    {
        return $this->prognosisFor($this->VO2maxShape, $this->Activity->distance());
    }

    public function getShapeDeviation(): string
    {
        $prognosis = $this->prognosisInSecondsFor($this->VO2maxShape, $this->Activity->distance());
        $duration = $this->Activity->duration();
        $difference = 100 * ($prognosis - $duration) / $duration;

        return sprintf('%01.2f', $difference);
    }

    public function getCorrectionFactor(): float
    {
        return (new LegacyEffectiveVO2maxCorrector())->fromActivity($this->Activity);
    }

    /**
     * @param float $vo2max [ml/kg/min]
     * @param float $distance [km]
     * @return float [s]
     */
    protected function prognosisInSecondsFor(float $vo2max, float $distance): float
    {
        return (new VO2max($vo2max))->getSeconds($distance);
    }

    /**
     * @param float $vo2max [ml/kg/min]
     * @param float $distance [km]
     */
    protected function prognosisFor(float $vo2max, float $distance): string
    {
        return $this->formatTime((int) $this->prognosisInSecondsFor($vo2max, $distance));
    }

    /**
     * @param int $seconds [s]
     */
    protected function formatTime(int $seconds): string
    {
        $Duration = new Duration($seconds);

        return $Duration->string(Duration::FORMAT_WITH_HOURS);
    }
}
