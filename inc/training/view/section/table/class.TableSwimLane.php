<?php

/**
 * This file contains class::TableSwimLane
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Data\Stroketype;
use Runalyze\Model\Activity\Splits;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Trackdata;

/**
 * Display swim lanes
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableSwimLane extends TableLapsAbstract
{
    protected function setCode()
    {
        // Nothing to do, the table is no longer built as a code string, see templateData().
    }

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $header = [
            $this->cell(''),
            $this->cell(__('Distance')),
            $this->cell(__('Time')),
            $this->cell(__('Swolf')),
            $this->cell(__('Strokes')),
            $this->cell(__('Type')),
        ];

        $rows = [];

        $Loop = new Swimdata\Loop($this->Context->swimdata());
        $Poollength = $this->Context->swimdata()
            ->poollength() / 100000;
        $TrackLoop = new Trackdata\Loop($this->Context->trackdata());
        $Stroketype = new Stroketype(\Runalyze\Profile\FitSdk\StrokeTypeProfile::FREESTYLE);
        $Distance = new Distance(0);

        $pauseTrackloopIdx = $this->getTrackloopPauseIdx($this->Context->activity()->splits(), $TrackLoop);

        $max = $Loop->num();
        $hasBreak = false;
        $interval = 1;
        $intervalLanes = 0;
        $swolfs = 0;
        $strokes = 0;

        for ($i = 1, $lane = 0; $i <= $max; ++$i) {
            $Stroketype->set($Loop->stroketype());
            $Distance->set($TrackLoop->distance());

            $lapFinished = in_array($TrackLoop->index(), $pauseTrackloopIdx);

            // #TSC: add more info to lanes-table: pause-lanes, summary line
            if ($lapFinished && $Stroketype->isBreak()) {
                if ($swolfs > 0 || $strokes > 0) {
                    // show the summary lane of this interval
                    $rows[] = $this->summaryRow($Poollength, $interval, $intervalLanes, $swolfs, $strokes);
                }

                $interval++;

                $intervalLanes = 0;
                $swolfs = 0;
                $strokes = 0;

                // TSC: pause/rest handling: if pause, show the pause lane
                $rows[] = [
                    'class' => 'r unimportant',
                    'style' => '',
                    'cells' => [
                        $this->cell(''),
                        $this->cell(''),
                        $this->cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME))),
                        $this->cell(''),
                        $this->cell(''),
                        $this->cell($Stroketype->shortString()),
                    ],
                ];

                $hasBreak = true;
            } else {
                $cells = [];

                if (! $Stroketype->isBreak()) {
                    $lane++;
                    $intervalLanes++;

                    $cells[] = $this->cell($lane . '.');
                    $cells[] = $this->cell($Distance->stringMeter());
                    $cells[] = $this->cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)));
                    $cells[] = $this->cell((string) $Loop->swolf());
                    $cells[] = $this->cell((string) $Loop->stroke());
                } else {
                    $cells[] = $this->cell('');
                    $cells[] = $this->cell('');
                    $cells[] = $this->cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)));
                    $cells[] = $this->cell('');
                    $cells[] = $this->cell('');
                }

                $swolfs += empty($Loop->swolf()) ? 0 : $Loop->swolf();
                $strokes += empty($Loop->stroke()) ? 0 : $Loop->stroke();

                $cells[] = $this->cell($Stroketype->shortString());

                $rows[] = [
                    'class' => 'r',
                    'style' => '',
                    'cells' => $cells,
                ];
            }

            $TrackLoop->nextStep();
            $Loop->nextStep();
        }

        // #TSC: in break/rest add the last interval "summary"; but only if the last is not a break interval
        if ($hasBreak && ! $Stroketype->isBreak()) {
            $rows[] = $this->summaryRow($Poollength, $interval, $intervalLanes, $swolfs, $strokes);
        }

        return [
            'class' => 'fullwidth zebra-style',
            'wrapper_style' => '',
            'warning' => '',
            'header' => $header,
            'bodies' => [
                [
                    'rows' => $rows,
                ],
            ],
        ];
    }

    // create a array with trackloop-indexes where the pause laps/rounds including #TSC
    /**
     * @return mixed[]
     */
    protected function getTrackloopPauseIdx(Splits\Entity $splits, Trackdata\Loop $trackLoop): array
    {
        $result = [];

        $time = 0;
        foreach ($splits->asArray() as $Split) {
            $time += $Split->time();
            if (! $Split->isActive()) {
                $trackLoop->moveToTime($time);
                $result[] = $trackLoop->index();
            }
        }

        // reset further use
        $trackLoop->reset();

        return $result;
    }

    /**
     * Summary row with infos about the previous lanes.
     * #TSC
     *
     * @return array<string, mixed>
     */
    protected function summaryRow(float $Poollength, int $interval, int $intervalLanes, int|float $swolfs, int|float $strokes): array
    {
        $distance = new Distance($Poollength * $intervalLanes);

        return [
            'class' => 'r unimportant',
            'style' => '',
            'cells' => [
                $this->cell($interval . './' . $intervalLanes),
                $this->cell($distance->stringMeter()),
                $this->cell(''),
                $this->cell('&#216; ' . ($intervalLanes !== 0 ? round($swolfs / $intervalLanes) : '-')),
                $this->cell('&#216; ' . ($intervalLanes !== 0 ? round($strokes / $intervalLanes) : '-')),
                $this->cell('Summary'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cell(string $content): array
    {
        return [
            'class' => '',
            'style' => '',
            'content' => $content,
        ];
    }
}
