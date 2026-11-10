<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Util\LocalTime;

class RaceAnalysis
{
    public function __construct(
        protected Training $Activity,
        protected float $VO2maxFactor,
        protected float $VO2maxShape
    ) {}

    /**
     * @TODO use Twig extension for LocalTime
     */
    public function getDate(): string
    {
        return LocalTime::date('d.m.Y', $this->Activity->getTime());
    }

    public function getName(): string
    {
        return $this->Activity->getTitle() ?? '';
    }

    public function getDistance(): string
    {
        $distance = new Distance($this->Activity->getDistance());

        if ($this->Activity->isTrack()) {
            return $distance->stringMeter();
        }

        return $distance->string();
    }

    public function getDuration(): string
    {
        return $this->formatTime((int) $this->Activity->getS());
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByTime(): ?float
    {
        return $this->Activity->getVO2maxByTime();
    }

    /**
     * @return ?int [bpm]
     */
    public function getHeartRateInBpm(): ?int
    {
        return $this->Activity->getPulseAvg();
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByHeartRate(): ?float
    {
        return $this->Activity->getVO2max();
    }

    public function getPrognosisByHeartRate(): string
    {
        return $this->prognosisFor($this->Activity->getVO2max(), $this->Activity->getDistance());
    }

    /**
     * @return ?float [ml/kg/min]
     */
    public function getVO2maxByHeartRateAfterCorrection(): ?float
    {
        return $this->VO2maxFactor * $this->Activity->getVO2max();
    }

    public function getPrognosisByHeartRateAfterCorrection(): string
    {
        return $this->prognosisFor($this->VO2maxFactor * $this->Activity->getVO2max(), $this->Activity->getDistance());
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
        return $this->prognosisFor($this->VO2maxShape, $this->Activity->getDistance());
    }

    public function getShapeDeviation(): string
    {
        $prognosis = $this->prognosisInSecondsFor($this->VO2maxShape, $this->Activity->getDistance());
        if ($prognosis === null) {
            return '-';
        }

        $duration = (int) $this->Activity->getS();
        $difference = 100 * ($prognosis - $duration) / $duration;
        return sprintf('%01.2f', $difference);
    }

    public function getCorrectionFactor(): float
    {
        $vo2maxByHeartRate = $this->Activity->getVO2max();

        return $vo2maxByHeartRate > 0.0 ? $this->Activity->getVO2maxByTime() / $vo2maxByHeartRate : 1.0;
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

        return new VO2max($vo2max)->getSeconds($distance);
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
