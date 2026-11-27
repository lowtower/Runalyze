<?php

/**
 * This file contains class::Pace
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Dataset key: Pace
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Pace extends AbstractKey
{
    // #TSC minimum- and maximum-values in "km/h" to bound the pace bar
    public const array P_MIN_MAX = [
        // km/h values means: pace between 12min/km - 3:20min/km
        SportProfile::RUNNING => [
            'min' => 5,
            'max' => 18,
        ],
        // km/h values means: pace between 3min/100m - 1:20min/100
        SportProfile::SWIMMING => [
            'min' => 2,
            'max' => 4.5,
        ],
        // 10 - 32 km/h
        SportProfile::CYCLING => [
            'min' => 10,
            'max' => 32,
        ],
    ];

    public const string DURATION_SUM_WITH_DISTANCE_KEY = 's_sum_with_distance';

    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::PACE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return '';
    }

    public function isInDatabase(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Pace');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        $pace = $this->secondsPerKilometer($context);
        if ($pace === null) {
            return '';
        }

        $unit = $context->hasSport() ? $context->sport()->getSpeedUnit() : new KilometerPerHour();

        return new PaceFormatter($unit)->format($pace);
    }

    /**
     * @return int see \Runalyze\Dataset\SummaryMode for enum
     */
    public function summaryMode(): int
    {
        return SummaryMode::AVG;
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small';
    }

    private function secondsPerKilometer(Context $context): ?int
    {
        $activity = $context->activity();
        $distance = $activity->distance();
        if ($distance === null || $distance <= 0) {
            return null;
        }

        $durationWithDistance = $context->data(self::DURATION_SUM_WITH_DISTANCE_KEY, true);
        if ($durationWithDistance !== null && $durationWithDistance <= 0) {
            return null;
        }

        return PaceFormatter::secondsPerKilometer((float) ($durationWithDistance ?? $activity->duration()), $distance);
    }

    /**
     * returns a value dependend CSS class and style.
     * #TSC
     * @return ?array with key "class" and "style"
     */
    public function valueDependendCssStyle(Context $context): ?array
    {
        if (! $context->hasSport()) {
            return null;
        }

        $sport = $context->sport();
        $minMax = self::P_MIN_MAX[$sport->getInternalProfileEnum()] ?? null;
        $pace = $this->secondsPerKilometer($context);
        if ($minMax === null || $pace === null || $pace <= 0) {
            return null;
        }

        $kmh = new KilometerPerHour()->fromBaseUnit($pace);
        $percentage = round(100 * ($kmh - $minMax['min']) / ($minMax['max'] - $minMax['min']));
        $timeFormat = $sport->getSpeedUnit() instanceof AbstractPaceInTimeFormatUnit;

        return [
            'class' => $timeFormat ? 'bar gray' : 'bar blue',
            'style' => '--perc: ' . ($timeFormat ? 100 - $percentage : $percentage) . '%;',
        ];
    }
}
