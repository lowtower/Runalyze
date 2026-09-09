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
    /**
     * @var int[]
     */
    protected array $UpperLimits = [
        1, 6, 12, 20, 29, 39, 50, 62, 75, 89, 103, 118, PHP_INT_MAX,
    ];

    /**
     * Beaufort number
     * @see https://en.wikipedia.org/wiki/Beaufort_scale#Modern_scale
     */
    protected ?int $Bft = null;

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
     * @codeCoverageIgnore
     */
    public static function getString(WindSpeed $windSpeed): string
    {
        return (new self($windSpeed))->string();
    }

    public static function getShortString(WindSpeed $windSpeed): string
    {
        return (new self($windSpeed))->shortString();
    }

    /**
     * Set bft value
     * @param int $bft
     * @return \Runalyze\Data\Weather\BeaufortScale $this-reference
     */
    public function set($bft): self
    {
        $this->Bft = $bft;

        return $this;
    }

    /**
     * Set wind speed
     * @return \Runalyze\Data\Weather\BeaufortScale $this-reference
     */
    public function setFromWindSpeed(WindSpeed $windSpeed): self
    {
        $kmh = $windSpeed->inKilometerPerHour();
        $this->Bft = 0;

        foreach ($this->UpperLimits as $bft => $upperLimit) {
            if ($kmh < $upperLimit) {
                $this->Bft = $bft;

                break;
            }
        }

        return $this;
    }

    public function string(bool $withUnit = true): string
    {
        if ($withUnit) {
            return $this->shortString();
        }

        return (string) $this->Bft;
    }

    /**
     * @codeCoverageIgnore
     */
    public function longString(): string
    {
        switch ($this->Bft) {
            case 0:
                return '0 bft (' . __('Calm') . ')';
            case 1:
                return '1 bft (' . __('Light air') . ')';
            case 2:
                return '2 bft (' . __('Light breeze') . ')';
            case 3:
                return '3 bft (' . __('Gentle breeze') . ')';
            case 4:
                return '4 bft (' . __('Moderate breeze') . ')';
            case 5:
                return '5 bft (' . __('Fresh breeze') . ')';
            case 6:
                return '6 bft (' . __('Strong breeze') . ')';
            case 7:
                return '7 bft (' . __('High wind') . ')';
            case 8:
                return '8 bft (' . __('Fresh gale') . ')';
            case 9:
                return '9 bft (' . __('Strong gale') . ')';
            case 10:
                return '10 bft (' . __('Whole gale') . ')';
            case 11:
                return '11 bft (' . __('Violent storm') . ')';
            case 12:
                return '12 bft (' . __('Hurricane force') . ')';
            default:
                return __('unknown');
        }
    }

    public function shortString(): string
    {
        if ($this->isValid()) {
            return $this->Bft . ' bft';
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
        return 'bft';
    }

    public function value(): ?int
    {
        return $this->Bft;
    }

    public function isValid(): bool
    {
        return is_numeric($this->Bft) && ($this->Bft >= 0) && ($this->Bft < count($this->UpperLimits));
    }
}
