<?php

/**
 * This file contains class::AnaerobicTrainingEffect
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * Training Effect
 *
 * @see https://www.firstbeat.com/en/consumer-products/features/#training-effect
 * @package Runalyze\Activity
 */
class AnaerobicTrainingEffect implements ValueInterface
{
    /**
     * @var float
     */
    public const LOWER_LIMIT = 0.0;

    /**
     * @var float
     */
    public const UPPER_LIMIT = 5.0;

    /**
     * @var int
     */
    public const DECIMALS = 1;

    protected ?float $Value = null;

    public static function format(mixed $value): string
    {
        return (new self($value))->string();
    }

    public function __construct(mixed $value = null)
    {
        $this->set($value);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        // #TSC: this label is shown on the statistic heart-rate "box"
        return __('Anaerob Training Effect');
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
     *
     * @throws \InvalidArgumentException
     */
    public function set(mixed $value): self
    {
        if ($value === null) {
            $this->Value = null;

            return $this;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Anaerobic Training Effect must be numeric.');
        }

        $value = (float) $value;

        if (is_nan($value) || $value < self::LOWER_LIMIT || $value > self::UPPER_LIMIT) {
            throw new \InvalidArgumentException(sprintf('Anaerobic Training Effect must be between %s and %s.', self::LOWER_LIMIT, self::UPPER_LIMIT));
        }

        $this->Value = $value;

        return $this;
    }

    /**
     * Format training effect as string
     */
    public function string(bool $withUnit = true): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        return number_format($this->Value, self::DECIMALS);
    }

    public function value(): ?float
    {
        return $this->Value;
    }

    public function numericValue(): float
    {
        return ($this->isKnown()) ? $this->Value : 0.0;
    }

    public function isKnown(): bool
    {
        return $this->Value !== null;
    }

    public function level(): ?int
    {
        if (! $this->isKnown()) {
            return null;
        }

        return TrainingEffectLevel::levelFor($this->Value);
    }

    public function shortDescription(): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        return TrainingEffectLevel::label($this->level());
    }

    public function description(): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        return TrainingEffectLevel::description($this->level());
    }
}
