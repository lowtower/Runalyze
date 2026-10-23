<?php

declare(strict_types=1);

/**
 * This file contains class::SelfEvaluationFeeling
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for SelfEvaluationFeeling (Gefuehl 1 - 5).
 *
 * @package Runalyze\Activity
 */
class SelfEvaluationFeeling extends AbstractEnum
{
    // respect the order by number!
    public const int VERY_WEAK = 1; // sehr schwach

    public const int WEAK = 2; // schwach

    public const int NORMAL = 3; // normal

    public const int STRONG = 4; // stark

    public const int VERY_STRONG = 5; // sehr stark

    /**
     * Converts the value from the FIT file to an enum.
     *
     * @param float $fitValue fitValue between 0 and 100
     * @return int internal enum
     * @throws \InvalidArgumentException
     */
    public static function fromFitToEnum(float $fitValue): int
    {
        if ((int) $fitValue < 0 || (int) $fitValue > 100) {
            throw new \InvalidArgumentException(sprintf('Provided evaluation effort %s is invalid.', $fitValue));
        }

        return match ((int) $fitValue) {
            0 => self::VERY_WEAK,
            25 => self::WEAK,
            50 => self::NORMAL,
            75 => self::STRONG,
            100 => self::VERY_STRONG,
            // return the original value as int / this results in an unknown "label"
            default => (int) $fitValue,
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
            self::VERY_WEAK => 'Sehr schwach &#x1F61E;',
            self::WEAK => 'Schwach &#x1F626;',
            self::NORMAL => 'Normal &#x1F610;',
            self::STRONG => 'Stark &#x1F642;',
            self::VERY_STRONG => 'Sehr stark &#x1F600;',
            default => sprintf('Unknown %u', $num),
        };
    }

    /**
     * Gets the number and label text as a string.
     *
     * @param int $num internal enum
     * @return string description with number and texts
     * @codeCoverageIgnore
     */
    public static function descriptionFromNum(int $num): string
    {
        return $num . ' ' . self::labelOfEnum($num);
    }
}
