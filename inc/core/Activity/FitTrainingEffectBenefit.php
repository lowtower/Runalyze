<?php

declare(strict_types=1);

/**
 * This file contains class::FitTrainingEffectBenefit
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for FitTrainingEffectBenefit (Nutzen 1 - 7).
 * #TSC
 * @package Runalyze\Activity
 */
class FitTrainingEffectBenefit extends AbstractEnum
{
    // respect the order by number!

    /**
     * @var int
     */
    public const RECOVERY = 1; // Erholung

    /**
     * @var int
     */
    public const BASE = 2; // Basis

    /**
     * @var int
     */
    public const PACE = 3; // Tempo

    /**
     * @var int
     */
    public const THRESHOLD = 4; // Schwelle

    /**
     * @var int
     */
    public const VO2MAX = 5; // VO2Max

    /**
     * @var int
     */
    public const ANAEROBIC = 6; // Anaerobic

    /**
     * @var int
     */
    public const SPRINT = 7; // Sprint

    /**
     * @var array
     */
    public const COLORS = [
        '#0aa476', // 1 green
        '#0aa476', // 2
        '#f27019', // 3 orange
        '#f27019', // 4
        '#f27019', // 5
        '#6439e4', // 6 blue violet
        '#6439e4', // 7
    ];

    /**
     * Converts the value from the FIT file to an enum.
     *
     * @param float $fitValue fitValue between 1 and 7
     * @return int internal enum
     * @throws \InvalidArgumentException
     */
    public static function fromFitToEnum(float $fitValue): int
    {
        if ((int) $fitValue < 1 || (int) $fitValue > 7) {
            throw new \InvalidArgumentException(sprintf('Provided TrainingEffect benefit %s is invalid.', $fitValue));
        }

        return match ((int) $fitValue) {
            1 => self::RECOVERY,
            2 => self::BASE,
            3 => self::PACE,
            4 => self::THRESHOLD,
            5 => self::VO2MAX,
            6 => self::ANAEROBIC,
            7 => self::SPRINT,
        };
    }

    /**
     * Gets the label text for the number.
     *
     * @param int $num internal enum
     * @codeCoverageIgnore
     */
    public static function labelOfEnum(int $num): string
    {
        return match ($num) {
            self::RECOVERY => 'Recovery',
            self::BASE => 'Base',
            self::PACE => 'Pace',
            self::THRESHOLD => 'Schwelle',
            self::VO2MAX => 'VO2Max',
            self::ANAEROBIC => 'Anaerobic',
            self::SPRINT => 'Sprint',
            default => sprintf('Unknown %u', $num),
        };
    }

    /**
     * Gets the label text as a string with color.
     *
     * @param int $num internal enum
     * @return string description with number and texts
     * @codeCoverageIgnore
     */
    public static function descriptionFromNum(int $num): string
    {
        if ($num > 0 && $num <= 7) {
            $col = self::COLORS[$num - 1];
        } else {
            $col = '#ffff00';
        }

        return '<span style="color:' . $col . ';">' . self::labelOfEnum($num) . '</span>';
    }
}
