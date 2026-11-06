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
            self::cell(''),
            self::cell(__('Distance')),
            self::cell(__('Time')),
            self::cell(__('Swolf')),
            self::cell(__('Strokes')),
            self::cell(__('Type')),
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
                        self::cell(''),
                        self::cell(''),
                        self::cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME))),
                        self::cell(''),
                        self::cell(''),
                        self::cell($Stroketype->shortString()),
                    ],
                ];

                $hasBreak = true;
            } else {
                $cells = [];

                if (! $Stroketype->isBreak()) {
                    $lane++;
                    $intervalLanes++;

                    $cells[] = self::cell($lane . '.');
                    $cells[] = self::cell($Distance->stringMeter());
                    $cells[] = self::cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)));
                    $cells[] = self::cell((string) $Loop->swolf());
                    $cells[] = self::cell((string) $Loop->stroke());
                } else {
                    $cells[] = self::cell('');
                    $cells[] = self::cell('');
                    $cells[] = self::cell(Duration::format($TrackLoop->difference(Trackdata\Entity::TIME)));
                    $cells[] = self::cell('');
                    $cells[] = self::cell('');
                }

                $swolfs += empty($Loop->swolf()) ? 0 : $Loop->swolf();
                $strokes += empty($Loop->stroke()) ? 0 : $Loop->stroke();

                $cells[] = self::cell($Stroketype->shortString());

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
                ['rows' => $rows],
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
     * @param int|float $swolfs
     * @param int|float $strokes
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
                self::cell($interval . './' . $intervalLanes),
                self::cell($distance->stringMeter()),
                self::cell(''),
                self::cell('&#216; ' . ($intervalLanes != 0 ? round($swolfs / $intervalLanes) : '-')),
                self::cell('&#216; ' . ($intervalLanes != 0 ? round($strokes / $intervalLanes) : '-')),
                self::cell('Summary'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cell(string $content): array
    {
        return [
            'class' => '',
            'style' => '',
            'content' => $content,
        ];
    }
}
