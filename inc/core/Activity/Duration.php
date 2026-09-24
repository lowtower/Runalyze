<?php

/**
 * This file contains class::Duration
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use DateTime;
use DateTimeZone;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class Duration
{
    /**
     * @var string
     */
    public const FORMAT_AUTO = 'auto';

    /**
     * @var string
     */
    public const FORMAT_COMPETITION = 'auto-competition';

    /**
     * @var string
     */
    public const FORMAT_WITH_DAYS = 'z\d H:i:s';

    /**
     * @var string
     */
    public const FORMAT_WITH_HOURS = 'G:i:s';

    public static string $DECIMAL_POINT = ',';

    /**
     * Time [s]
     */
    protected float $Time;

    /**
     * @param float|string|int $input seconds as float or "[[[z\d ]H:]i:]s[(.|,)u]"
     */
    public static function format(int|float|string $input): string
    {
        $Object = new self($input);

        return $Object->string();
    }

    /**
     * Create duration
     * @param float|string|int $input seconds as float or "[[[z\d ]H:]i:]s[(.|,)u]"
     */
    public function __construct(int|float|string $input = 0)
    {
        if (is_numeric($input)) {
            $this->fromSeconds($input);
        } else {
            $this->fromString($input);
        }
    }

    /**
     * Set duration from seconds
     * @return \Runalyze\Activity\Duration $this-reference
     */
    public function fromSeconds(float $seconds): self
    {
        $this->Time = $seconds;

        return $this;
    }

    /**
     * @param string $string format: "[[[z\d ]H:]i:]s[(.|,)u]"
     * @return \Runalyze\Activity\Duration $this-reference
     */
    public function fromString(string $string): self
    {
        $this->Time = 0;

        // #TSC - fix: only set duration if something exists in the string
        if ($string !== '') {
            $split = explode('.', str_replace(',', '.', $string));

            if (isset($split[1])) {
                $this->Time += (float) ('0.' . $split[1]);
            }

            $splitDays = explode('d ', $split[0]);

            if (isset($splitDays[1])) {
                $this->Time += 86400 * (float) $splitDays[0];
                $splitDays[0] = $splitDays[1];
            }

            $parts = explode(':', $splitDays[0]);
            $num = count($parts);

            foreach ($parts as $i => $part) {
                $this->Time += (float) $part * 60 ** ($num - $i - 1);
            }
        }

        return $this;
    }

    public function seconds(): float
    {
        return $this->Time;
    }

    /**
     * @param string $format format accepted by date()
     * @param int $decimals number of decimals, only if 'u' is present in $format
     */
    public function string(string $format = self::FORMAT_AUTO, int $decimals = 2): string
    {
        if ($format === self::FORMAT_AUTO) {
            return $this->autoString($decimals);
        }

        if ($format === self::FORMAT_COMPETITION) {
            return $this->autoCompetitionString($decimals);
        }

        return $this->formatString($format, $decimals);
    }

    protected function autoString(int $decimals = 2): string
    {
        $fraction = (round($this->Time) !== round($this->Time, $decimals) && $decimals > 0) ? self::$DECIMAL_POINT . 'u' : '';

        if ($this->Time >= 86400) {
            return $this->formatString(self::FORMAT_WITH_DAYS);
        }

        if ($this->Time >= 3600) {
            return $this->formatString(self::FORMAT_WITH_HOURS);
        }

        if ($this->Time < 60) {
            return '0:' . $this->formatString('s' . $fraction, $decimals);
        }

        return ltrim($this->formatString('i:s' . $fraction, $decimals), '0');
    }

    protected function autoCompetitionString(int $decimals = 2): string
    {
        if ($this->Time >= 60 || $this->Time == 0) {
            return $this->autoString($decimals);
        }

        return ltrim($this->formatString('s' . self::$DECIMAL_POINT . 'u', $decimals), '0') . 's';
    }

    /**
     * Format with DateTime object
     */
    protected function formatString(string $format, int $decimals = 2): string
    {
        if (str_ends_with($format, 'u')) {
            $fraction = str_pad((string) round(fmod($this->Time, 1) * 10 ** $decimals), $decimals, '0', STR_PAD_LEFT);
            return $this->formatString(substr($format, 0, -1)) . $fraction;
        }

        $time = DateTime::createFromFormat('!U', (string) (int) round($this->Time, $decimals), new DateTimeZone('UTC'));

        if ($time === false) {
            throw new \InvalidArgumentException("Can't format time (t = " . (int) round($this->Time, $decimals) . ').');
        }

        if ($format === self::FORMAT_WITH_HOURS) {
            /* we need to compute the hours ourselves, since DateTime outputs %G as [0..24) */
            $sec = $time->format('U');
            $s = $sec % 60;
            $m = (($sec - $s) / 60) % 60;
            $h = floor($sec / 3600);
            return $h . ':' . substr('0' . $m, -2) . ':' . substr('0' . $s, -2);
        }

        return $time->format($format);
    }

    /**
     * @return \Runalyze\Activity\Duration $this-reference
     */
    public function multiply(float $factor): self
    {
        $this->Time *= $factor;

        return $this;
    }

    /**
     * @return \Runalyze\Activity\Duration $this-reference
     */
    public function add(self $object): self
    {
        $this->Time += $object->seconds();

        return $this;
    }

    /**
     * @return \Runalyze\Activity\Duration $this-reference
     */
    public function subtract(self $object): self
    {
        $this->Time -= $object->seconds();

        return $this;
    }

    public function isNegative(): bool
    {
        return $this->Time < 0;
    }

    public function isZero(): bool
    {
        return $this->Time == 0;
    }

    /**
     * @param bool $invert by default, larger is 'better'; set to true to invert that
     * @throws \InvalidArgumentException
     */
    public function compareTo(self $other, bool $invert = false, bool $raw = false): string
    {
        if ($this->seconds() == 0 || $other->seconds() == 0) {
            return '';
        }

        $CompareTime = new self(round(abs($this->seconds() - $other->seconds())));
        $isPositive = ! $invert ? $this->seconds() > $other->seconds() : $this->seconds() <= $other->seconds();

        return $this->formatComparison($CompareTime->string(), $isPositive, $raw);
    }

    /**
     * @param string $string e.g. '0:27'
     */
    protected function formatComparison(string $string, bool $isPositive, bool $raw = false): string
    {
        $class = ($isPositive) ? 'plus' : 'minus';
        $sign = ($isPositive) ? '+' : '-';

        if ($raw) {
            return $sign . $string;
        }

        /**
         * @codeCoverageIgnore
         */
        return '<span class="' . $class . '">' . $sign . $string . '</span>';
    }
}
