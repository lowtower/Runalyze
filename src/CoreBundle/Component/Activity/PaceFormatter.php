<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Activity\Duration;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;

final readonly class PaceFormatter
{
    public function __construct(
        private AbstractPaceUnit $unit
    ) {}

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
        if ($this->isTimeFormat()) {
            $value = $secondsPerKilometer === 0.0
                ? '-:--'
                : Duration::format(round($this->unit->fromBaseUnit($secondsPerKilometer)));

            return $value . $this->unit->getAppendix();
        }

        return number_format($this->unit->fromBaseUnit($secondsPerKilometer), 1, ',', '') . '&nbsp;' . $this->unit->getAppendix();
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
