<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity\Tool;

use Runalyze\Calculation\Math\SubSegmentMaximization;
use Runalyze\Model\Trackdata;

class BestSubSegmentsStatistics
{
    protected \Runalyze\Model\Trackdata\Entity $Trackdata;

    protected ?SubSegmentMaximization $DistanceSegments = null;

    protected ?SubSegmentMaximization $TimeSegments = null;

    protected array $Distances = [];

    protected array $Times = [];

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(Trackdata\Entity $trackdata)
    {
        if (! $trackdata->has(Trackdata\Entity::TIME) || ! $trackdata->has(Trackdata\Entity::DISTANCE)) {
            throw new \InvalidArgumentException('Provided trackdata object must have time and distance array.');
        }

        $this->Trackdata = $trackdata;
    }

    public function setDistancesToAnalyze(array $distances): void
    {
        $this->Distances = $distances;
    }

    public function setTimesToAnalyze(array $times): void
    {
        $this->Times = $times;
    }

    public function findSegments(): void
    {
        $num = $this->Trackdata->num();
        $time = $this->Trackdata->time();
        $distance = $this->Trackdata->distance();
        $timeAsDeltas = $time;
        $distanceAsDeltas = $distance;

        for ($i = 1; $i < $num; ++$i) {
            $timeAsDeltas[$i] = $time[$i] - $time[$i - 1];
            $distanceAsDeltas[$i] = $distance[$i] - $distance[$i - 1];
        }

        $this->DistanceSegments = new SubSegmentMaximization($timeAsDeltas, $distanceAsDeltas, $this->Distances);
        $this->DistanceSegments->minimize();

        $this->TimeSegments = new SubSegmentMaximization($distanceAsDeltas, $timeAsDeltas, $this->Times);
        $this->TimeSegments->maximize();
    }

    public function getDistanceSegments(): ?SubSegmentMaximization
    {
        return $this->DistanceSegments;
    }

    public function getTimeSegments(): ?SubSegmentMaximization
    {
        return $this->TimeSegments;
    }

    public function getDistances(): array
    {
        return $this->Distances;
    }

    public function getTimes(): array
    {
        return $this->Times;
    }
}
