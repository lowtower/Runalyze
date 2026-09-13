<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingEffectLevel
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for training effect levels
 *
 * @see https://www.firstbeat.com/en/consumer-products/features/#training-effect
 * @package Runalyze\Activity
 */
class TrainingEffectLevel extends AbstractEnum
{
    /**
     * @var int
     */
    public const EASY = 1;

    /**
     * @var int
     */
    public const MAINTAINING = 2;

    /**
     * @var int
     */
    public const IMPROVING = 3;

    /**
     * @var int
     */
    public const HIGHLY_IMPROVING = 4;

    /**
     * @var int
     */
    public const OVERREACHING = 5;

    /**
     * @param float $value value between 1.0 and 5.0
     * @return int internal enum
     * @throws \InvalidArgumentException
     */
    public static function levelFor(float $value): int
    {
        if ($value < 1.0 || $value > 5.0) {
            throw new \InvalidArgumentException(sprintf('Provided training effect %s is invalid.', $value));
        }

        if ($value === 5.0) {
            return self::OVERREACHING;
        }

        if ($value >= 4.0) {
            return self::HIGHLY_IMPROVING;
        }

        if ($value >= 3.0) {
            return self::IMPROVING;
        }

        if ($value >= 2.0) {
            return self::MAINTAINING;
        }

        return self::EASY;
    }

    /**
     * @param int $enum internal enum
     * @throws \InvalidArgumentException
     * @codeCoverageIgnore
     */
    public static function label(int $enum): string
    {
        return match ($enum) {
            self::EASY => __('Easy'),
            self::MAINTAINING => __('Maintaining workout'),
            self::IMPROVING => __('Improving Fitness'),
            self::HIGHLY_IMPROVING => __('Highly Improving'),
            self::OVERREACHING => __('Overreaching'),
            default => throw new \InvalidArgumentException(sprintf('Provided level %u is invalid.', $enum)),
        };
    }

    /**
     * @param int $enum internal enum
     * @throws \InvalidArgumentException
     * @codeCoverageIgnore
     */
    public static function description(int $enum): string
    {
        return match ($enum) {
            self::EASY => __('Helps recovery (short activities). Improves endurance with longer activities (more than 40 minutes).'),
            self::MAINTAINING => __('Maintains your aerobic fitness.'),
            self::IMPROVING => __('Improves your aerobic fitness if repeated as part of your weekly training program.'),
            self::HIGHLY_IMPROVING => __('Highly improves your aerobic fitness if repeated 1-2 times per week with adequate recovery time.'),
            self::OVERREACHING => __('Causes temporary overload with high improvement. Train up to this number with extreme care. Requires additional recovery days.'),
            default => throw new \InvalidArgumentException(sprintf('Provided level %u is invalid.', $enum)),
        };
    }
}
