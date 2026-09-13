<?php

declare(strict_types=1);

/**
 * This file contains class::Pause
 * @package Runalyze\Model\Trackdata
 */

namespace Runalyze\Model\Trackdata;

/**
 * @deprecated since v4.2, use \Runalyze\Parser\Activity\Common\Data\Pause\Pause instead
 */
class Pause
{
    /**
     * Key: time
     */
    public const TIME = 'time';

    /**
     * Key: duration
     */
    public const DURATION = 'duration';

    /**
     * Key: heart rate start
     */
    public const HR_START = 'hr-start';

    /**
     * Key: heart rate end
     */
    public const HR_END = 'hr-end';

    /**
     * Construct
     */
    public function __construct(
        /**
         * Timestamp
         * Relative to activity
         */
        protected int $Time = 0,
        /**
         * Duration
         */
        protected float $Duration = 0,
        /**
         * HR start
         */
        protected ?int $HRstart = null,
        /**
         * HR end
         */
        protected ?int $HRend = null
    ) {
    }

    /**
     * Set data from array
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data)
    {
        $this->Time = $data[self::TIME] ?? 0;
        $this->Duration = $data[self::DURATION] ?? 0;
        $this->HRstart = $data[self::HR_START] ?? null;
        $this->HRend = $data[self::HR_END] ?? null;
    }

    /**
     * As array
     * @return array<string, float|int|null>
     */
    public function asArray(): array
    {
        return [
            self::TIME => $this->Time,
            self::DURATION => $this->Duration,
            self::HR_START => $this->HRstart,
            self::HR_END => $this->HRend,
        ];
    }

    /**
     * Has heart rate info?
     */
    public function hasHeartRateInfo(): bool
    {
        return $this->HRstart !== null && $this->HRend !== null;
    }

    public function time(): int
    {
        return $this->Time;
    }

    public function duration(): float
    {
        return $this->Duration;
    }

    /**
     * Heart rate at start
     */
    public function hrStart(): ?int
    {
        return $this->HRstart;
    }

    /**
     * Heart rate at end
     */
    public function hrEnd(): ?int
    {
        return $this->HRend;
    }

    /**
     * Heart rate difference
     * @return int A negative value indicates an increase
     */
    public function hrDiff(): int
    {
        return $this->HRstart - $this->HRend;
    }
}
