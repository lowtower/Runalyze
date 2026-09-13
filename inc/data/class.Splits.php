<?php

/**
 * This file contains class::Splits
 * @package Runalyze\Data\Splits
 */

use Runalyze\Activity\Duration;

/**
 * Class for handling splits
 * @author Hannes Christiansen
 * @package Runalyze\Data\Splits
 */
class Splits
{
    /**
     * Splits as string
     */
    private string $asString = '';

    /**
     * Splits as array
     * @var array<mixed, array<string, string|bool>>
     */
    private array $asArray = [];

    protected bool $transformDistanceUnit = false;

    /**
     * Construct a new object of Splits
     * @param array|string $data [optional]
     * @param array<string, mixed> $options
     */
    public function __construct($data = '', array $options = [])
    {
        if (isset($options['transform-unit']) && $options['transform-unit'] === true) {
            $this->transformDistanceUnit = true;
        }

        $this->createFromData($data);
    }

    /**
     * Create splits from POST-data
     * @param mixed $data
     */
    private function createFromData($data): void
    {
        if (is_array($data)) {
            $this->createFromArray($data);
            $this->cleanArray();
            $this->arrayToString();
        } else {
            $this->asString = $data;
            $this->stringToArray();
            $this->cleanArray();
            $this->arrayToString();
        }

        $this->removeSingleSplits();
    }

    /**
     * @param array<string, mixed> $array
     */
    private function createFromArray(array $array): void
    {
        $this->asArray = [];
        $factor = $this->transformDistanceUnit ? Runalyze\Configuration::General()->distanceUnitSystem()->distanceToKmFactor() : 1;

        // TODO escaping
        if (isset($array['km']) && isset($array['time'])) {
            foreach ($array['km'] as $i => $km) {
                $this->asArray[] = [
                    'km' => $km * $factor,
                    'time' => $array['time'][$i],
                    'active' => ! isset($array['active']) || (isset($array['active']) && isset($array['active'][$i]) && $array['active'][$i]),
                ];
            }
        }
    }

    /**
     * Are the splits empty?
     */
    public function areEmpty(): bool
    {
        return $this->asArray === [];
    }

    /**
     * Get splits as array
     * @return array<mixed, array<string, string|bool>>
     */
    public function asArray(): array
    {
        return $this->asArray;
    }

    /**
     * Get splits as string
     */
    public function asString(): string
    {
        return $this->asString;
    }

    /**
     * @param float $km kilometer
     * @param int $timeInSeconds seconds
     * @param bool $active optional
     */
    public function addSplit(float $km, int $timeInSeconds, bool $active = true): void
    {
        if ($km <= 0 && $timeInSeconds <= 0) {
            return;
        }

        $this->asArray[] = [
            'km' => $this->formatKM($km),
            'time' => $this->formatTime($timeInSeconds),
            'active' => $active,
        ];
        $this->arrayToString();
    }

    /**
     * Calculate and add last split
     * Add last split to fill up remaining time and distance,
     * calculated as difference between given distance/duration and current sum.
     * @param float $totalDistance [km] should be larger then current sum of splits
     * @param int $totalDuration [s] should be larger then current sum of splits
     */
    public function addLastSplitToComplete(float $totalDistance, int $totalDuration, bool $active = true): void
    {
        $distance = $totalDistance - $this->totalDistance();
        $time = $totalDuration - $this->totalTime();

        if ($distance >= 0 && $time > 0) {
            $this->addSplit($distance, $time, $active);
        }
    }

    public function removeSingleSplits(): void
    {
        if (count($this->asArray) === 1) {
            $this->asArray = [];
            $this->asString = '';
        }
    }

    /**
     * Get splits as readable string
     * @param bool $restingLaps optional
     */
    public function asReadableString(bool $restingLaps = false): string
    {
        $strings = [];

        foreach ($this->asArray as $split) {
            if ($restingLaps || $split['active']) {
                $strings[] = $split['km'] . '&nbsp;km&nbsp;' . __('in') . '&nbsp;' . $split['time'] . (! $split['active'] ? '&nbsp;(' . __('Resting') . ')' : '');
            }
        }

        return implode(', ', $strings);
    }

    /**
     * As icon with tooltip
     */
    public function asIconWithTooltip(): string
    {
        if (! $this->areEmpty()) {
            return Ajax::tooltip(Icon::$CLOCK, $this->asReadableString());
        }

        return '';
    }

    /**
     * Transform splits from internal string to array
     */
    private function stringToArray(): void
    {
        $this->asArray = [];
        $splits = explode('-', str_replace('\r\n', '-', $this->asString));

        foreach ($splits as $split) {
            if (str_starts_with($split, 'R')) {
                $active = false;
                $split = substr($split, 1);
            } else {
                $active = true;
            }

            if (strlen($split) > 3) {
                $this->asArray[] = [
                    'km' => strstr($split, '|', true),
                    'time' => substr(strrchr($split, '|'), 1),
                    'active' => $active,
                ];
            }
        }
    }

    /**
     * Clean internal array
     */
    private function cleanArray(): void
    {
        foreach ($this->asArray as $key => $split) {
            // if ($split['km'] <= 0)
            // if ($split['km'] <= 0 || empty($split['time']))
            //	unset($this->asArray[$key]);
            // else
            $this->asArray[$key]['km'] = $this->formatKM($split['km']);

            if (str_ends_with($split['time'], 's')) {
                $this->asArray[$key]['time'] = $this->formatTime((float) substr($split['time'], 0, -1));
            }
        }
    }

    /**
     * Format kilometer
     */
    private function formatKM(string $km): string
    {
        return number_format(Helper::CommaToPoint($km), 3, '.', '');
    }

    private function formatTime(float $seconds): string
    {
        return Duration::format(round($seconds));
    }

    /**
     * Transform internal array to string
     */
    private function arrayToString(): void
    {
        $strings = [];

        foreach ($this->asArray() as $split) {
            $strings[] = ($split['active'] ? '' : 'R') . $split['km'] . '|' . $split['time'];
        }

        $this->asString = implode('-', $strings);
    }

    /**
     * Get all times as array
     * @param bool $restingLaps optional
     * @return float[]
     */
    public function timesAsArray(bool $restingLaps = false): array
    {
        $times = [];

        foreach ($this->asArray as $split) {
            if ($restingLaps || $split['active']) {
                $Duration = new Duration($split['time']);
                $times[] = $Duration->seconds();
            }
        }

        return $times;
    }

    /**
     * Get total time
     */
    public function totalTime(): int
    {
        $time = 0;

        foreach ($this->asArray as $split) {
            $Duration = new Duration($split['time']);
            $time += $Duration->seconds();
        }

        return $time;
    }

    /**
     * Is at least one lap active?
     */
    public function hasActiveLaps(int $num = 1): bool
    {
        $count = 0;
        foreach ($this->asArray as $split) {
            if ($split['active']) {
                $count++;
            }

            if ($count === $num) {
                return true;
            }
        }

        return false;
    }

    /**
     * Are there active and inactive laps?
     */
    public function hasActiveAndInactiveLaps(): bool
    {
        $active = null;

        foreach ($this->asArray as $split) {
            if ($active === null) {
                $active = $split['active'];
            } elseif ($active != $split['active']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all distances as array
     * @param bool $restingLaps optional
     */
    public function distancesAsArray(bool $restingLaps = false): array
    {
        $distances = [];

        foreach ($this->asArray as $split) {
            if ($restingLaps || $split['active']) {
                $distances[] = $split['km'];
            }
        }

        return $distances;
    }

    /**
     * Get total distance
     */
    public function totalDistance(): float
    {
        $km = 0;

        foreach ($this->asArray as $split) {
            $km += $split['km'];
        }

        return $km;
    }

    /**
     * Get all paces as array
     * @param bool $restingLaps optional
     * @return int[]
     */
    public function pacesAsArray(bool $restingLaps = false): array
    {
        $paces = [];

        foreach ($this->asArray as $split) {
            if ($restingLaps || $split['active']) {
                $Duration = new Duration($split['time']);
                $paces[] = $split['km'] > 0 ? (int) round($Duration->seconds() / $split['km']) : 0;
            }
        }

        return $paces;
    }

    /**
     * Set empty times from array
     */
    public function fillTimesFromArray(array $Time, array $Distance): void
    {
        $this->fillFromArray($Time, $Distance, 'time');
    }

    /**
     * Set empty times from array
     */
    public function fillDistancesFromArray(array $Time, array $Distance): void
    {
        $this->fillFromArray($Time, $Distance, 'km');
    }

    /**
     * Set empty times from array
     * @param array<int, mixed> $Time
     * @param array<int, mixed> $Distance
     */
    protected function fillFromArray(array $Time, array $Distance, string $mode = 'time')
    {
        $totalDistance = 0;
        $totalTime = 0;
        $size = min(count($Time), count($Distance));
        $i = 0;

        foreach ($this->asArray as &$split) {
            if ($mode === 'km') {
                $Duration = new Duration($split['time']);

                while ($i < $size - 1 && $Duration->seconds() > $Time[$i] - $totalTime) {
                    $i++;
                }

                $split['km'] = $this->formatKM($Distance[$i] - $totalDistance);
            } else {
                while ($i < $size - 1 && $split['km'] > $Distance[$i] - $totalDistance) {
                    $i++;
                }

                $split['time'] = $this->formatTime($Time[$i] - $totalTime);
            }

            $totalTime = $Time[$i];
            $totalDistance = $Distance[$i];
        }

        $this->arrayToString();
    }
}
