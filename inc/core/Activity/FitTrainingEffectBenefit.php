<?php
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

	/** @var int */
	const RECOVERY = 1; // Erholung

	/** @var int */
	const BASE = 2; // Basis

    /** @var int */
    const PACE = 3; // Tempo

    /** @var int */
    const THRESHOLD = 4; // Schwelle

    /** @var int */
    const VO2MAX = 5; // VO2Max

    /** @var int */
    const ANAEROBIC = 6; // Anaerobic

    /** @var int */
    const SPRINT = 7; // Sprint

    /** @var array */
    const COLORS = [
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
    public static function fromFitToEnum($fitValue)
    {
        if (!is_numeric($fitValue) || (int)$fitValue < 1 || (int)$fitValue > 7 ) {
            throw new \InvalidArgumentException(sprintf('Provided TrainingEffect benefit %s is invalid.', $fitValue));
        }

        switch ((int)$fitValue) {
            case 1:
                return self::RECOVERY;
            case 2:
                return self::BASE;
            case 3:
                return self::PACE;
            case 4:
                return self::THRESHOLD;
            case 5:
                return self::VO2MAX;
            case 6:
                return self::ANAEROBIC;
            case 7:
                return self::SPRINT;
            default:
                // return the original value / this results in an unknown "label"
                return $fitValue;
        }
    }

    /**
     * Gets the label text for the number.
     *
     * @param int $num internal enum
     * @return string
     * @codeCoverageIgnore
     */
    public static function labelOfEnum($num)
    {
        switch ($num) {
            case self::RECOVERY:
                return 'Recovery';
            case self::BASE:
                return 'Base';
            case self::PACE:
                return 'Pace';
            case self::THRESHOLD:
                return 'Schwelle';
            case self::VO2MAX:
                return 'VO2Max';
            case self::ANAEROBIC:
                return 'Anaerobic';
            case self::SPRINT:
                return 'Sprint';
            default:
                return sprintf('Unknown %u', $num);
        }
    }

    /**
     * Gets the label text as a string with color.
     *
     * @param int $enum internal enum
     * @return description with number and texts
     * @codeCoverageIgnore
     */
    public static function descriptionFromNum($num)
    {
        if (is_numeric($num) && (int)$num > 0&& (int)$num <= 7) {
            $col = self::COLORS[$num - 1];
        } else {
            $col = '#ffff00';
        }

        return '<span style="color:' . $col . ';">' . self::labelOfEnum($num) . '</span>';
    }
}
