<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals;

use DateTimeInterface;

final readonly class GoalsProgressCalculator
{
    /**
     * @return list<array<string, int|float|string|bool|null>>
     */
    public function calculate(
        DateTimeInterface $start,
        DateTimeInterface $end,
        DateTimeInterface $today,
        float $distanceSum,
        int $activityCount,
        float $goal,
        bool $showDetails
    ): array {
        $days = 1 + (int) $start->diff($today)->format('%a');
        $duration = 1 + (int) $start->diff($end)->format('%a');
        $remainingDays = $duration - $days;
        $rows = [];

        if ($distanceSum > 0) {
            $rows[] = $this->headline(__('Current'), $distanceSum, $activityCount, true);

            if ($showDetails) {
                $rows[] = $this->line(__('avg.') . ' ' . __('Day'), $distanceSum / $days);

                if ($days > 7) {
                    $rows[] = $this->line(__('avg.') . ' ' . __('Week'), 7 * $distanceSum / $days, $activityCount / $days * 7);
                }

                $rows[] = $this->headline(__('Prognosis'), $distanceSum / $days * $duration, $activityCount / $days * $duration);
            }
        }

        if ($goal <= 0) {
            return $rows;
        }

        $rows[] = $this->headline(__('Goal'), $goal);

        if ($distanceSum < $goal) {
            $toGo = $goal - $distanceSum;
            $rows[] = $this->line(sprintf(__('%d days left'), $remainingDays), $toGo);

            if ($showDetails) {
                $rows[] = $this->line(__('avg.') . ' ' . __('Day'), $remainingDays > 0 ? $toGo / $remainingDays : 0);

                if ($remainingDays > 7) {
                    $rows[] = $this->line(__('avg.') . ' ' . __('Week'), 7 * $toGo / $remainingDays);
                }
            }
        } else {
            $rows[] = $this->line(__('Goal reached.'));
        }

        $difference = $goal / $duration * $days - $distanceSum;

        if ($showDetails) {
            $rows[] = $this->headline(__('Pace Bunny'), $goal / $duration * $days);
            $rows[] = $this->line(__('avg.') . ' ' . __('Day'), $goal / $duration);
            $rows[] = $this->line($difference > 0 ? __('Behind') : __('Ahead'), abs($difference));
            $rows[] = $this->headline(__('Head 2 Head'));
        }

        $rows[] = $this->bar(__('Me'), $distanceSum, $goal, $difference > 0 ? '#f99' : '#9f9');
        $rows[] = $this->bar(__('Pace&nbsp;Bunny'), $goal / $duration * ($days - 1), $goal, '#ccf', $goal / $duration, '#ddf');

        return $rows;
    }

    /**
     * @return array{name: string, lvl: int, sep: bool, km: float, anz: float}
     */
    private function headline(string $name, float $distance = 0, float $activities = 0, bool $withoutBorder = false): array
    {
        return [
            'name' => $name,
            'lvl' => 1,
            'sep' => ! $withoutBorder,
            'km' => $distance,
            'anz' => $activities,
        ];
    }

    /**
     * @return array{name: string, lvl: int, km: float, anz: float}
     */
    private function line(string $name, float $distance = 0, float $activities = 0): array
    {
        return [
            'name' => $name,
            'lvl' => 2,
            'km' => $distance,
            'anz' => $activities,
        ];
    }

    /**
     * @return array{name: string, type: string, val: float, max: float, color: string, add: ?float, addcolor: ?string}
     */
    private function bar(string $name, float $value, float $max, string $color, ?float $add = null, ?string $addcolor = null): array
    {
        return [
            'name' => $name,
            'type' => 'bar',
            'val' => $value,
            'max' => $max,
            'color' => $color,
            'add' => $add,
            'addcolor' => $addcolor,
        ];
    }
}
