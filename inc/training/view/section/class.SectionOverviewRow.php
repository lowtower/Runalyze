<?php

/**
 * This file contains class::SectionOverviewRow
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Box;

/**
 * Row: Overview
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionOverviewRow extends TrainingViewSectionRowFullwidth
{
    /**
     * Boxed values
     * @var BoxedValue[]
     */
    protected $BoxedValues = [];

    protected function setContent()
    {
        $this->id = 'training-overview';

        $this->setBoxedValues();

        $NumberOfBoxes = count($this->BoxedValues);
        foreach ($this->BoxedValues as $Value) {
            $Value->defineAsFloatingBlockWithFixedWidth($NumberOfBoxes);
        }
    }

    /**
     * Template data of the boxed values
     *
     * @return array<int, array<string, mixed>>
     */
    protected function boxedValuesTemplateData(): array
    {
        $data = [];

        foreach ($this->BoxedValues as $Value) {
            $data[] = $Value->templateData();
        }

        return $data;
    }

    protected function setBoxedValues()
    {
        $this->BoxedValues = [
            new Box\Distance($this->Context),
            new BoxedValue($this->Context->dataview()->duration()->string(), '', __('Time')),
            new BoxedValue($this->Context->dataview()->elapsedTime(), '', __('Elapsed time')),
            new Box\Pace($this->Context),
            new BoxedValue(Helper::Unknown($this->Context->activity()->hrAvg(), '-'), 'bpm', __('avg.') . ' ' . __('Heart rate')),
            new BoxedValue(Helper::Unknown($this->Context->activity()->hrMax(), '-'), 'bpm', __('max.') . ' ' . __('Heart rate')),
            new Box\Energy($this->Context),
            new BoxedValue(Helper::Unknown($this->Context->dataview()->vo2max()->value(), '-'), '', 'VO<sub>2</sub>max', $this->Context->dataview()->effectiveVO2maxIcon(), 'vo2max'),
            new Box\Trimp($this->Context),
            new Box\Elevation($this->Context),
        ];
    }
}
