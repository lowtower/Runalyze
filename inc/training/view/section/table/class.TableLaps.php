<?php

/**
 * This file contains class::TableLaps
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Model\Trackdata;
use Runalyze\Util\StringReader;
use Runalyze\View\Splits;

/**
 * Table: laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableLaps extends TableLapsAbstract
{
    protected function setCode()
    {
        $Reader = new StringReader($this->Context->activity()->title());

        $Splits = $this->Context->activity()
            ->splits();
        $SplitsView = new Splits\Table($Splits, $this->Context->dataview()->pace()->unitEnum());
        $SplitsView->setDemandedPace($Reader->findDemandedPace());

        if ($this->Context->trackdata()->has(Trackdata\Entity::DISTANCE) && $this->Context->hasRaceResult()) {
            $SplitsView->setHalfsOfCompetition($this->computeHalfs());
        }

        $this->Code = $SplitsView->code();
    }

    protected function computeHalfs(): array
    {
        $Halfs = [];

        $Loop = new Trackdata\Loop($this->Context->trackdata());
        $Loop->moveToDistance($this->Context->trackdata()->totalDistance() / 2);
        $Halfs[] = $this->halfFromLoop($Loop);

        $Loop->moveToDistance($this->Context->trackdata()->totalDistance());
        $Halfs[] = $this->halfFromLoop($Loop);

        return $Halfs;
    }

    /**
     * @return array<string, float>
     */
    protected function halfFromLoop(Trackdata\Loop $Loop): array
    {
        return [
            's' => $Loop->difference(Trackdata\Entity::TIME),
            'km' => $Loop->difference(Trackdata\Entity::DISTANCE),
        ];
    }
}
