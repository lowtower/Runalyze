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
    protected ?Splits\Table $SplitsView = null;

    protected function setCode()
    {
        $this->Code = $this->splitsView()->code();
    }

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        return $this->splitsView()->templateData();
    }

    protected function splitsView(): Splits\Table
    {
        if (! $this->SplitsView instanceof \Runalyze\View\Splits\Table) {
            $Reader = new StringReader($this->Context->activity()->title());

            $Splits = $this->Context->activity()
                ->splits();
            $SplitsView = new Splits\Table($Splits, $this->Context->dataview()->pace()->unitEnum());
            $SplitsView->setDemandedPace($Reader->findDemandedPace());

            if ($this->Context->trackdata()->has(Trackdata\Entity::DISTANCE) && $this->Context->hasRaceResult()) {
                $SplitsView->setHalfsOfCompetition($this->computeHalfs());
            }

            $this->SplitsView = $SplitsView;
        }

        return $this->SplitsView;
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
