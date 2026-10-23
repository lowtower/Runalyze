<?php

/**
 * This file contains class::TrainingEffect
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * Training Effect
 *
 * @see https://www.firstbeat.com/en/consumer-products/features/#training-effect
 * @package Runalyze\Activity
 */
class TrainingEffect implements ValueInterface
{
    public const float LOWER_LIMIT = 0.0;

    public const float UPPER_LIMIT = 5.0;

    public const int DECIMALS = 1;

    protected ?float $Value = null;

    public static function format(?float $value): string
    {
        return new self($value)->string();
    }

    /**
     * @param float|null $value
     */
    public function __construct($value = null)
    {
        $this->set($value);
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        // #TSC: Add Aerobic-prefix
        return __('(Aerob) Training Effect');
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
     * @param float|null|string $value
     * @return \Runalyze\Activity\TrainingEffect $this-reference
     * @throws \InvalidArgumentException
     */
    public function set($value): self
    {
        if ($value === null) {
            $this->Value = null;

            return $this;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Training Effect must be a number, not ' . $value);
        }

        $value = (float) $value;
        if ($value < self::LOWER_LIMIT || $value > self::UPPER_LIMIT) {
            throw new \InvalidArgumentException(sprintf('Training Effect must be between %s and %s.', self::LOWER_LIMIT, self::UPPER_LIMIT));
        }

        $this->Value = $value;

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

    /**
     * @codeCoverageIgnore
     */
    public function shortDescription(): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        return TrainingEffectLevel::label($this->level());
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        if (! $this->isKnown()) {
            return '';
        }

        return TrainingEffectLevel::description($this->level());
    }
}
