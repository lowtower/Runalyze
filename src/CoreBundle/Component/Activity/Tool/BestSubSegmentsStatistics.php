<?php

declare(strict_types=1);

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

    /**
     * @throws \InvalidArgumentException if no distances have been configured via setDistancesToAnalyze()
     */
    public function findDistanceSegments(): void
    {
        if ($this->Distances === []) {
            throw new \InvalidArgumentException('No activity distances configured; call setDistancesToAnalyze() first.');
        }

        [$timeAsDeltas, $distanceAsDeltas] = $this->computeDeltas();

        $this->DistanceSegments = new SubSegmentMaximization($timeAsDeltas, $distanceAsDeltas, $this->Distances);
        $this->DistanceSegments->minimize();
    }

    /**
     * @throws \InvalidArgumentException if no times have been configured via setTimesToAnalyze()
     */
    public function findTimeSegments(): void
    {
        if ($this->Times === []) {
            throw new \InvalidArgumentException('No activity times configured; call setTimesToAnalyze() first.');
        }

        [$timeAsDeltas, $distanceAsDeltas] = $this->computeDeltas();

        $this->TimeSegments = new SubSegmentMaximization($distanceAsDeltas, $timeAsDeltas, $this->Times);
        $this->TimeSegments->maximize();
    }

    /**
     * @return array{0: array, 1: array} [timeAsDeltas, distanceAsDeltas]
     */
    private function computeDeltas(): array
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

        return [$timeAsDeltas, $distanceAsDeltas];
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
