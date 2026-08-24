<?php

/**
 * This file contains class::BeaufortScale
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use Runalyze\Activity\ValueInterface;

/**
 * Beaufort scale
 * @author Michael Pohl
 * @see https://en.wikipedia.org/wiki/Beaufort_scale#Modern_scale
 * @package Runalyze\Data\Weather
 */
class BeaufortScale implements ValueInterface
{
    public $btf;

    /**
     * @var array
     */
    protected $UpperLimits = [
        1, 6, 12, 20, 29, 39, 50, 62, 75, 89, 103, 118, PHP_INT_MAX,
    ];

	/**
	 * Beaufort number
	 * @var int|null
	 * @see https://en.wikipedia.org/wiki/Beaufort_scale#Modern_scale
	 */
	protected $Btf;

	/**
	 * Wind condition
	 */
	public function __construct(?WindSpeed $windSpeed = null)
	{
	    if ($windSpeed instanceof \Runalyze\Data\Weather\WindSpeed) {
			$this->setFromWindSpeed($windSpeed);
	    }
	}

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public static function getString(WindSpeed $windSpeed)
	{
        return (new self($windSpeed))->string();
	}

    public static function getShortString(WindSpeed $windSpeed): string
	{
        return (new self($windSpeed))->shortString();
	}

	/**
	 * Set btf value
	 * @param int $btf beaufort number
	 * @return \Runalyze\Data\Weather\BeaufortScale $this-reference
	 */
	public function set($btf): self
	{
	    $this->Btf = $btf;

		return $this;
	}

    /**
     * Set wind speed
     * @return \Runalyze\Data\Weather\BeaufortScale $this-reference
     */
    public function setFromWindSpeed(WindSpeed $windSpeed): self
	{
	    $kmh = $windSpeed->inKilometerPerHour();
		$this->Btf = 0;

        foreach ($this->UpperLimits as $bft => $upperLimit) {
        	if ($kmh < $upperLimit) {
                $this->Btf = $bft;

                break;
            }
        }

		return $this;
	}

	/**
	 * @param bool $withUnit
	 * @return string
	 */
	public function string($withUnit = true)
	{
	    if ($withUnit) {
			return $this->shortString();
	    }

		return $this->Btf;
	}

    /**
     * String
     * @codeCoverageIgnore
     */
    public function longString(): string
	{
		switch ($this->btf) {
			case 0:
				return '0 btf (' . __('Calm') . ')';
			case 1:
				return '1 btf (' . __('Light air') . ')';
			case 2:
				return '2 btf (' . __('Light breeze') . ')';
			case 3:
				return '3 btf (' . __('Gentle breeze') . ')';
			case 4:
				return '4 btf (' . __('Moderate breeze') . ')';
			case 5:
				return '5 btf (' . __('Fresh breeze') . ')';
			case 6:
				return '6 btf (' . __('Strong breeze') . ')';
			case 7:
				return '7 btf (' . __('High wind') . ')';
			case 8:
				return '8 btf (' . __('Fresh gale') . ')';
			case 9:
				return '9 btf (' . __('Strong gale') . ')';
			case 10:
				return '10 btf (' . __('Whole gale') . ')';
			case 11:
				return '11 btf (' . __('Violent storm') . ')';
			case 12:
				return '12 btf (' . __('Hurricane force') . ')';
			default:
				return __('unknown');
		}
	}

    public function shortString(): string
	{
	    if ($this->isValid()) {
			return $this->Btf . ' btf';
	    }

	    return '';
	}

    /**
     * Label for value
     * @codeCoverageIgnore
     */
    public function label(): string
	{
	    return __('Beaufort scale');
	}

    /**
     * Label for value
     */
    public function unit(): string
	{
	    return 'btf';
	}

	/**
	 * @return int|null
	 */
	public function value()
	{
	    return $this->Btf;
	}

	public function isValid(): bool
	{
		return is_numeric($this->Btf) && ($this->Btf >= 0) && ($this->Btf < count($this->UpperLimits));
	}
}
