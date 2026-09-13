<?php

/**
 * This file contains class::PerformanceCondition
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * FIT performance condition
 *
 * This value represents the current athlete's condition, based on his (by Garmin/Firstbeat) estimated VO2max.
 * A value of 100 is interpreted as 100% which equals the current baseline value.
 * A value of e.g. 97 is shown as "-3" on the device (usually within the first 20 minutes).
 *
 * @see http://www8.garmin.com/manuals/webhelp/fenix3/EN-US/GUID-901ACCFC-FB0D-414E-B80C-54970AF4E357.html
 * @see https://www.youtube.com/watch?v=14gYdT8W2Ts
 * @package Runalyze\Activity
 */
class PerformanceCondition implements ValueInterface
{
    /**
     * @var int
     */
    public const BASELINE = 100;

    /**
     * @var int
     */
    public const LOWER_LIMIT = 80;

    /**
     * @var int
     */
    public const UPPER_LIMIT = 120;

    protected ?int $Value = null;

    public static function format(?int $value): string
    {
        return (new self($value))->string();
    }

    public function __construct(?int $value = null)
    {
        $this->set($value);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Performance condition');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('PC');
    }

    /**
     * @codeCoverageIgnore
     */
    public function unit(): string
    {
        return '';
    }

    /**
     * Set training effect
     * @param int|null|string $value
     * @return \Runalyze\Activity\PerformanceCondition $this-reference
     * @throws \InvalidArgumentException
     */
    public function set($value): self
    {
        if ($value === null || (int) $value < self::LOWER_LIMIT || (int) $value > self::UPPER_LIMIT) {
            $this->Value = null;
        } else {
            $this->Value = (int) $value;
        }

        return $this;
    }

    /**
     * Format training effect as string
     * @param bool $withUnit [optional] show unit?
     */
    public function string(bool $withUnit = true): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        $baselineValue = $this->Value - self::BASELINE;

        if ($baselineValue > 0) {
            $baselineValue = '+' . $baselineValue;
        } elseif ($baselineValue === 0) {
            $baselineValue = '&plusmn;0';
        }

        return $baselineValue;
    }

    public function value(): ?int
    {
        return $this->Value;
    }

    public function isKnown(): bool
    {
        return $this->Value !== null;
    }
}
