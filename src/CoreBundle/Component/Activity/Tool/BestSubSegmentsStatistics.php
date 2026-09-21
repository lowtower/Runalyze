<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity\Tool;

use Runalyze\Calculation\Math\SubSegmentMaximization;
use Runalyze\Model\Trackdata;

class BestSubSegmentsStatistics
{
    protected \Runalyze\Model\Trackdata\Entity $Trackdata;

    /**
     * @var SubSegmentMaximization|null
     */
    protected $DistanceSegments;

    /**
     * @var SubSegmentMaximization|null
     */
    protected $TimeSegments;

    /**
     * @var array
     */
    protected $Distances = [];

    /**
     * @var array
     */
    protected $Times = [];

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

    /**
     * @return null|SubSegmentMaximization
     */
    public function getDistanceSegments()
    {
        return $this->DistanceSegments;
    }

    /**
     * @return null|SubSegmentMaximization
     */
    public function getTimeSegments()
    {
        return $this->TimeSegments;
    }

    /**
     * @return array
     */
    public function getDistances()
    {
        return $this->Distances;
    }

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->Times;
    }
}
