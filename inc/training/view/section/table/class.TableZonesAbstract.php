<?php

/**
 * This file contains class::TableZonesAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;

/**
 * Display zones
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TableZonesAbstract
{
    /**
     * @var int [s]
     */
    public const MINIMUM_TIME_IN_ZONE = 10;

    protected array $Data = [];

    private string $TitlePercent = ''; // #TSC

    public function __construct(
        protected \Runalyze\View\Activity\Context $Context
    ) {
        if ($this->Context->trackdata()->has(Runalyze\Model\Trackdata\Entity::TIME)) {
            $this->initData();
            $this->convertData();
        }
    }

    /**
     * Get title for average
     */
    public function titleForAverage(): string
    {
        return '';
    }

    /**
     * Show average?
     */
    private function showAverage(): bool
    {
        return $this->titleForAverage() !== '';
    }

    abstract protected function initData();

    /**
     * Get hint for footer #TSC
     */
    public function footerHint(): string
    {
        return '';
    }

    private function convertData()
    {
        $totalTime = 0;
        $totalDist = 0;

        foreach ($this->Data as $Info) {
            $totalTime += $Info['time'];
            $totalDist += $Info['distance'];
        }

        foreach ($this->Data as $i => $Info) {
            if ($totalTime > 0) {
                $percentage = round(100 * $Info['time'] / $totalTime, 1);
                $this->TitlePercent = 'Time';
            } elseif ($totalDist > 0) {
                $percentage = round(100 * $Info['distance'] / $totalDist, 1);
                $this->TitlePercent = 'Distance';
            } else {
                $percentage = '-';
            }

            $this->Data[$i]['percentage'] = $percentage;
            $this->Data[$i]['time'] = $totalTime > 0 ? Duration::format($Info['time']) : '-';
            $this->Data[$i]['distance'] = $totalDist > 0 ? Distance::format($Info['distance']) : '-';
        }
    }

    /**
     * Get template data to display the zones table
     *
     * @return array<string, mixed>|null template data, null if there are no zone data
     */
    final public function templateData(): ?array
    {
        if ($this->Data === []) {
            return null;
        }

        return [
            'template' => 'activity/training_view/table_zones.html.twig',
            'header' => $this->headerTemplateData(),
            'show_average' => $this->showAverage(),
            'rows' => $this->rowsTemplateData(),
            'footer_hint' => $this->footerHint() !== '' ? HTML::info($this->footerHint()) : '',
        ];
    }

    /**
     * Get header cells
     *
     * @return array<int, array<string, string>>
     */
    private function headerTemplateData(): array
    {
        $header = [
            [
                'content' => __('Zone'),
                'style' => '',
            ],
            [
                'content' => '% ' . __($this->TitlePercent),
                'style' => 'text-align: left;',
            ], // #TSC set the table column title
            [
                'content' => __('Time'),
                'style' => '',
            ],
            [
                'content' => __('Distance'),
                'style' => '',
            ],
        ];

        if ($this->showAverage()) {
            $header[] = [
                'content' => $this->titleForAverage(),
                'style' => '',
            ];
        }

        return $header;
    }

    /**
     * Get data rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsTemplateData(): array
    {
        $rows = [];

        foreach ($this->Data as $Info) {
            $rows[] = [
                'zone' => $Info['zone'],
                'percentage' => $Info['percentage'],
                'color' => $Info['color'] ?? null, // #TSC support of colors for the percent bar
                'time' => $Info['time'],
                'distance' => $Info['distance'],
                'average' => $Info['average'],
            ];
        }

        return $rows;
    }
}
