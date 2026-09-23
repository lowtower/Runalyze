<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector;
use Runalyze\Model\Activity\Entity;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Util\LocalTime;

class RaceAnalysis
{
    public function __construct(
        protected Entity $Activity,
        protected float $VO2maxFactor,
        protected float $VO2maxShape
    ) {
    }

    /**
     * @TODO use Twig extension for LocalTime
     */
    public function getDate(): string
    {
        return LocalTime::date('d.m.Y', $this->Activity->timestamp());
    }

    public function getName(): string
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
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByTime(): ?float
    {
        return $this->Activity->vo2maxByTime();
    }

    /**
     * @return ?int [bpm]
     */
    public function getHeartRateInBpm(): ?int
    {
        return $this->Activity->hrAvg();
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByHeartRate(): ?float
    {
        return $this->Activity->vo2maxByHeartRate();
    }

    public function getPrognosisByHeartRate(): string
    {
        return $this->prognosisFor($this->Activity->vo2maxByHeartRate(), $this->Activity->distance());
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByHeartRateAfterCorrection(): ?float
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
    public function getVO2maxByShape(): float
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
        if ($prognosis === null) {
            return '-';
        }

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
     * @return ?float [s]
     */
    protected function prognosisInSecondsFor(float $vo2max, ?float $distance): ?float
    {
        if ($distance === null) {
            return null;
        }

        return (new VO2max($vo2max))->getSeconds($distance);
    }

    /**
     * @param float $vo2max [ml/kg/min]
     * @param float $distance [km]
     */
    protected function prognosisFor(float $vo2max, ?float $distance): string
    {
        if ($distance === null) {
            return '-';
        }

        $seconds = $this->prognosisInSecondsFor($vo2max, $distance);
        if ($seconds === null) {
            return '-';
        }

        return $this->formatTime((int) $seconds);
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
