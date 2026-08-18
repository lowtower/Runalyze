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
	/** @var float */
	const LOWER_LIMIT = 0.0;

	/** @var float */
	const UPPER_LIMIT = 5.0;

    /** @var int */
    const DECIMALS = 1;

	/** @var float|null */
	protected $Value;

	/**
	 * Format
	 * @param float|null $value
	 * @return string
	 */
	public static function format($value): string
    {
        return (new self($value))->string();
	}

	/**
	 * @param float|null $value
	 */
	public function __construct($value = null)
    {
		$this->set($value);
	}

    /**
     * @return string
     * @codeCoverageIgnore
     */
	public function label(): string
    {
		// #TSC: Add Aerobic-prefix
		return __('(Aerob) Training Effect');
	}

	/**
	 * Unit
	 * @return string
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
	public function set($value) {
        if (null === $value) {
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
		if ($value < self::LOWER_LIMIT || self::UPPER_LIMIT < $value) {
			throw new \InvalidArgumentException(sprintf('Training Effect must be between %s and %s.', self::LOWER_LIMIT, self::UPPER_LIMIT));
		}

		$this->Value = $value;

		return $this;
	}

	/**
	 * Format training effect as string
	 * @param bool $withUnit [optional] show unit?
	 * @return string
	 */
	public function string($withUnit = true): string {
        if (! $this->isKnown()) {
            return '';
        }

		return number_format($this->Value, self::DECIMALS);
	}

	/**
	 * @return float|null
	 */
	public function value()
    {
		return $this->Value;
	}

    /**
     * @return float
     */
    public function numericValue()
    {
        return ($this->isKnown()) ? $this->Value : 0.0;
    }

    /**
     * @return bool
     */
    public function isKnown(): bool
    {
        return (null !== $this->Value);
    }

    /**
     * @return int|null
     */
    public function level()
    {
        if (! $this->isKnown()) {
            return null;
        }

        return TrainingEffectLevel::levelFor($this->Value);
    }

    /**
     * @return string
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
     * @return string
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
