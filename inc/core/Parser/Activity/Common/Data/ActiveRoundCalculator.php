<?php

/**
 * This file contains class::ActiveRoundCalculator
 * @package Runalyze\Calculation\Activity
 */

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

/**
 * Calculate values based only on active rounds. Inactive rounds will be ignored.
 *
 * @author #TSC
 * @package Runalyze\Calculation\Activity
 */
class ActiveRoundCalculator
{
    protected $continuousData;

    protected $activeIdx;

    public function __construct(ContinuousData $continuousData, RoundCollection $rounds)
    {
        $this->continuousData = $continuousData;

        if (! $rounds->isEmpty()) {
            $this->init($rounds);
        }
    }

    /**
     * builds the activeIdx array this is corresponding with the other ContinuousData arrays.
     * for every index which is TRUE, the value is in a active round/lap.
     *
     * data examples:
     *
     * from the split/runalyze_training.splits:
     * round    duration    from-to
     * 1        549,92      0 - 549,92
     * 2        396,08      549,93 - 946,01 (add 0.01 to the duration of #1)
     * ...
     *
     * from runalyze_tackdata.time; all in seconds
     * runalyze_tackdata    lap#    round split
     * time	                        time
     *
     * 38                   1       0
     * ...
     * 551                          549,92
     * 947                  break   549,93 (add 0,01 to duration) (+ 396,08) till 946,01
     * 974                  2       946,02
     * ...
     */
    protected function init(RoundCollection $rounds)
    {
        $time = $this->continuousData->Time;
        $len = count($time);

        // fill array with true's
        $this->activeIdx = array_fill(0, $len, true);

        if ($len === 0) {
            return;
        }

        $roundTimeStart = 0.0;
        $timeIdx = 0;
        foreach ($rounds as $round) {
            // find the start of the inactive round
            if (! $round->isActive()) {
                // Find the first track point after the beginning of the inactive round.
                while ($timeIdx < $len && $time[$timeIdx] <= $roundTimeStart) {
                    $timeIdx++;
                }

                // Select the track point closest to the round boundary. This is only possible if points exist on both sides.
                if ($timeIdx > 0 && $timeIdx < $len && $roundTimeStart - $time[$timeIdx - 1] > $time[$timeIdx] - $roundTimeStart) {
                    $timeIdx++;
                }

                // now the idx is on the break start
            }

            // the rounds have only durations, so add the duration to get the "next round start time"
            $roundTimeStart += $round->getDuration() + 0.01;

            if (! $round->isActive()) {
                // Mark all track points belonging to the inactive round.
                while ($timeIdx < $len && $time[$timeIdx] <= $roundTimeStart) {
                    $this->activeIdx[$timeIdx] = false;
                    $timeIdx++;
                }

                // Include the following point if it is closer to the end boundary than the preceding point.
                if ($timeIdx > 0 && $timeIdx < $len && $roundTimeStart - $time[$timeIdx - 1] > $time[$timeIdx] - $roundTimeStart) {
                    $this->activeIdx[$timeIdx] = false;
                    $timeIdx++;
                }
            }

            if ($timeIdx >= $len) {
                break;
            }
        }
    }

    /**
     * returns the avg heart-rate of all active rounds.
     */
    public function calcAvgHeartRate()
    {
        $hr = $this->continuousData->HeartRate;

        if (empty($this->activeIdx) || empty($hr)) {
            return null;
        }

        $activeHeartRates = [];

        foreach ($this->activeIdx as $key => $isActive) {
            if ($isActive && array_key_exists($key, $hr) && $hr[$key] !== null && is_numeric($hr[$key])) {
                $activeHeartRates[] = $hr[$key];
            }
        }

        if ($activeHeartRates === []) {
            return null;
        }

        return (int) round(
            array_sum($activeHeartRates) / count($activeHeartRates)
        );
    }
}
