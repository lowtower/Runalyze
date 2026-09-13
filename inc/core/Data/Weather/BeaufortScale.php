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
        return match ($this->Bft) {
            0 => '0 bft (' . __('Calm') . ')',
            1 => '1 bft (' . __('Light air') . ')',
            2 => '2 bft (' . __('Light breeze') . ')',
            3 => '3 bft (' . __('Gentle breeze') . ')',
            4 => '4 bft (' . __('Moderate breeze') . ')',
            5 => '5 bft (' . __('Fresh breeze') . ')',
            6 => '6 bft (' . __('Strong breeze') . ')',
            7 => '7 bft (' . __('High wind') . ')',
            8 => '8 bft (' . __('Fresh gale') . ')',
            9 => '9 bft (' . __('Strong gale') . ')',
            10 => '10 bft (' . __('Whole gale') . ')',
            11 => '11 bft (' . __('Violent storm') . ')',
            12 => '12 bft (' . __('Hurricane force') . ')',
            default => __('unknown'),
        };
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
