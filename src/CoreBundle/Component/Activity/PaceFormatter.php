<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use InvalidArgumentException;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;

final readonly class PaceFormatter
{
    public function __construct(
        private AbstractPaceUnit $unit
    ) {}

    public static function forSport(?Sport $sport): self
    {
        try {
            return new self($sport?->getSpeedUnit() ?? new KilometerPerHour());
        } catch (InvalidArgumentException) {
            return new self(new KilometerPerHour());
        }
    }

    public static function secondsPerKilometer(float $seconds, float $kilometers): int
    {
        return $kilometers > 0.0 ? (int) round($seconds / $kilometers) : 0;
    }

    public function isTimeFormat(): bool
    {
        return $this->unit instanceof AbstractPaceInTimeFormatUnit;
    }

    public function format(float $secondsPerKilometer): string
    {
        return $this->value($secondsPerKilometer) . $this->appendix();
    }

    public function value(float $secondsPerKilometer): string
    {
        if ($this->isTimeFormat()) {
            return $secondsPerKilometer === 0.0
                ? '-:--'
                : Duration::format(round($this->unit->fromBaseUnit($secondsPerKilometer)));
        }

        return number_format($this->unit->fromBaseUnit($secondsPerKilometer), 1, ',', '');
    }

    public function appendix(): string
    {
        return ($this->isTimeFormat() ? '' : '&nbsp;') . $this->unit->getAppendix();
    }

    public function compare(int $pace, int $reference): string
    {
        if ($pace === 0 || $reference === 0) {
            return '';
        }

        $difference = $reference - $pace;
        if (! $this->isTimeFormat() && $difference !== 0) {
            $difference = $pace * $reference / $difference;
        }

        $class = $difference >= 0 ? 'plus' : 'minus';
        $sign = $difference >= 0 ? '+' : '-';
        $value = $difference === 0 && $this->isTimeFormat()
            ? '0:00' . $this->unit->getAppendix()
            : $this->format(abs($difference));

        return '<span class="' . $class . '">' . $sign . $value . '</span>';
    }
}
