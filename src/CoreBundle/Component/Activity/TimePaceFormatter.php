<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Activity\Duration;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;

final readonly class TimePaceFormatter
{
    public function __construct(
        private AbstractPaceInTimeFormatUnit $unit
    ) {}

    public static function secondsPerKilometer(float $seconds, float $kilometers): int
    {
        return $kilometers > 0.0 ? (int) round($seconds / $kilometers) : 0;
    }

    public function format(int $secondsPerKilometer): string
    {
        $value = $secondsPerKilometer === 0
            ? '-:--'
            : Duration::format(round($this->unit->fromBaseUnit($secondsPerKilometer)));

        return $value . $this->unit->getAppendix();
    }

    public function compare(int $pace, int $reference): string
    {
        if ($pace === 0 || $reference === 0) {
            return '';
        }

        $difference = $reference - $pace;
        $class = $difference >= 0 ? 'plus' : 'minus';
        $sign = $difference >= 0 ? '+' : '-';
        $value = $difference === 0 ? '0:00' . $this->unit->getAppendix() : $this->format(abs($difference));

        return '<span class="' . $class . '">' . $sign . $value . '</span>';
    }
}
