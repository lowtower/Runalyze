<?php

/**
 * This file contains class::TableSwimLaneAggregateInterval
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Data\Stroketype;
use Runalyze\Model\Activity\Splits;
use Runalyze\Model\Swimdata;
use Runalyze\Model\Trackdata;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Display swim lanes in details of 100m intervals.
 * #TSC
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableSwimLaneAggregateInterval extends TableLapsAbstract
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
        $header = [];
        foreach ([
            __('Laps'),
            __('Distance'),
            '',
            __('Pace'),
            '',
            __('Pace') . ' ' . __('diff'),
            __('Swolf'),
            __('Swolf') . ' ' . __('diff'),
            __('Strokes'),
            __('Strokes') . ' ' . __('diff'),
            __('Type'),
        ] as $content) {
            $header[] = self::cell($content);
        }

        $rows = [];

        // needed data
        $Loop = new Swimdata\Loop($this->Context->swimdata());
        $poollength = $this->Context->swimdata()
            ->poollength() / 100000; // in KM
        $Stroketype = new Stroketype(\Runalyze\Profile\FitSdk\StrokeTypeProfile::FREESTYLE);
        $distance = new Distance(0);	// whole distance
        $distance100 = new Distance(0); // distance for every 100m interval
        $duration = new Duration(0);	// duration for every 100m interval

        $TrackLoop = new Trackdata\Loop($this->Context->trackdata());
        // creates an array with indexes of the TrackLoop lap-changes
        $lapTrackloopIdx = $this->getTrackloopLapIdx($this->Context->activity()->splits(), $TrackLoop);

        $max = $Loop->num();

        // aggregated values and the previous value for diff
        $swolfs = 0;
        $prevSwolfs = 0;

        $strokes = 0;
        $prevStrokes = 0;

        $pace = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_100M);
        $prevPace = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_100M);

        $stroketypePrimary = [];

        for ($i = 1, $lap = 1, $beginLap = true; $i <= $max; ++$i) {
            $Stroketype->set($Loop->stroketype());
            $distance->set($TrackLoop->distance());
            $distance100->add(new Distance($TrackLoop->difference(Trackdata\Entity::DISTANCE))); // in KM

            // if no break aggregate and write 100m interval row
            if (! $Stroketype->isBreak()) {
                $duration->add(new Duration($TrackLoop->difference(Trackdata\Entity::TIME)));

                // aggregate values (pace is diff by the TrackLoop)
                $swolfs += empty($Loop->swolf()) ? 0 : $Loop->swolf();
                $strokes += empty($Loop->stroke()) ? 0 : $Loop->stroke();

                // calc a array where the type is the id; later the id with the highest value can be used for primary type
                $stroketypePrimary[$Stroketype->id()] = array_key_exists($Stroketype->id(), $stroketypePrimary) ? $stroketypePrimary[$Stroketype->id()] + 1 : 1;

                $lapFinished = in_array($TrackLoop->index(), $lapTrackloopIdx);

                // if 100m exceeded or the lap is finished write the table row
                if ($distance100->stringMeter(false) === '100' || $lapFinished) {
                    $cells = [];

                    // round/lap
                    $cells[] = self::cell((string) $lap);

                    // distance cumulated and per 100meter
                    $cells[] = self::cell($distance->stringMeter());
                    $cells[] = self::cell($distance100->stringMeter());

                    // pace
                    $pace->setTime($duration->seconds());
                    $pace->setDistance($distance100->value());
                    $cells[] = self::cell($pace->valueWithAppendix());
                    // make it very easy: the pace seconds define the bar-width
                    $cells[] = [
                        'class' => '',
                        'style' => 'text-align: left; padding-left: 2%;',
                        'content' => '<span class="bar-chart-value" style="width:' . ($pace->secondsPerKm() / 10) . 'px; top: 0px;"></span>',
                    ];
                    if ($beginLap && $lap === 1) {
                        $cells[] = self::cell('');
                    } else {
                        $cells[] = self::cell('<span style="opacity: ' . ($beginLap ? '0.5' : '1.0') . '">' . $pace->compareTo($prevPace) . '</span>');
                    }

                    // swolfs
                    $sw = round($swolfs / ($distance100->value() / $poollength));
                    if ($beginLap && $lap === 1) {
                        $cells[] = self::cell('');
                        $cells[] = self::cell('');
                    } else {
                        $cells[] = self::cell((string) $sw);
                        $cells[] = self::cell($this->difference($prevSwolfs, $sw, $beginLap));
                    }

                    // strokes
                    if ($beginLap && $lap === 1) {
                        $cells[] = self::cell('');
                        $cells[] = self::cell('');
                    } else {
                        $cells[] = self::cell((string) $strokes);
                        $cells[] = self::cell($this->difference($prevStrokes, $strokes, $beginLap));
                    }

                    $primaryStroketypeId = array_search(max($stroketypePrimary), $stroketypePrimary, true);
                    $Stroketype->set($primaryStroketypeId);
                    $cells[] = [
                        'class' => '',
                        'style' => 'padding-right: 20px;',
                        'content' => $Stroketype->shortString(),
                    ];

                    $rows[] = [
                        'class' => 'r',
                        'style' => $beginLap ? 'border-color: #666; border-style: dashed dashed dashed dashed; border-width: 1px 0px 1px 0px;' : '',
                        'cells' => $cells,
                    ];

                    // store "previous" values for diff
                    $prevPace->setTime($duration->seconds());
                    $prevPace->setDistance($distance100->value());
                    $prevSwolfs = $sw;
                    $prevStrokes = $strokes;

                    // reset values
                    $swolfs = 0;
                    $strokes = 0;
                    $duration->fromSeconds(0);
                    $distance100->set(0);
                    $stroketypePrimary = [];

                    // increase lap counter
                    if ($lapFinished) {
                        ++$lap;
                        $beginLap = true;
                    } else {
                        $beginLap = false;
                    }
                }
            }

            $TrackLoop->nextStep();
            $Loop->nextStep();
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

    // create a array with trackloop-indexes where the laps/rounds changing
    /**
     * @return mixed[]
     */
    protected function getTrackloopLapIdx(Splits\Entity $splits, Trackdata\Loop $trackLoop): array
    {
        $result = [];

        $time = 0;
        foreach ($splits->asArray() as $Split) {
            $time += $Split->time();
            if ($Split->isActive()) {
                $trackLoop->moveToTime($time);
                $result[] = $trackLoop->index();
            }
        }

        // reset further use
        $trackLoop->reset();

        return $result;
    }

    // calculates the difference between two values and return the diff with a pretty colored span
    protected function difference(float $previous, float $new, bool $beginLap): string
    {
        $diff = $previous - $new;
        if ($diff > 0) {
            $class = 'plus';
            $sign = '+';
        } else {
            $class = 'minus';
            $sign = ' ';
        }

        return '<span class="' . $class . '" style="opacity: ' . ($beginLap ? '0.5' : '1.0') . '">' . $sign . $diff . '</span>';
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
