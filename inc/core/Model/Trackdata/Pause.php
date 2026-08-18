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
class Pause {
	/**
	 * Key: time
	 */
	const TIME = 'time';

	/**
	 * Key: duration
	 */
	const DURATION = 'duration';

	/**
	 * Key: heart rate start
	 */
	const HR_START = 'hr-start';

	/**
	 * Key: heart rate end
	 */
	const HR_END = 'hr-end';

	/**
	 * Timestamp
	 * Relative to activity
	 * @var int
	 */
	protected $Time = 0;

	/**
	 * Duration
	 * @var int
	 */
	protected $Duration = 0;

	/**
	 * HR start
	 * @var int
	 */
	protected $HRstart;

	/**
	 * HR end
	 * @var int
	 */
	protected $HRend;

	/**
	 * Construct
	 * @param int $time
	 * @param int $duration
	 * @param int $hrStart
	 * @param int $hrEnd
	 */
	public function __construct($time = 0, $duration = 0, $hrStart = null, $hrEnd = null) {
		$this->Time = $time;
		$this->Duration = $duration;
		$this->HRstart = $hrStart;
		$this->HRend = $hrEnd;
	}

    /**
     * Set data from array
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data) {
		$this->Time = $data[self::TIME] ?? 0;
		$this->Duration = $data[self::DURATION] ?? 0;
		$this->HRstart = $data[self::HR_START] ?? null;
		$this->HRend = $data[self::HR_END] ?? null;
	}

    /**
     * As array
     * @return array<string, int>
     */
    public function asArray(): array {
		return array(
    self::TIME => $this->Time,
		    self::DURATION => $this->Duration,
		    self::HR_START => $this->HRstart,
		    self::HR_END => $this->HRend
);
	}

    /**
     * Has heart rate info?
     */
    public function hasHeartRateInfo(): bool {
		return null !== $this->HRstart && null !== $this->HRend;
	}

	/**
	 * Time
	 * @return int
	 */
	public function time() {
		return $this->Time;
	}

	/**
	 * Duration
	 * @return int
	 */
	public function duration() {
		return $this->Duration;
	}

	/**
	 * Heart rate at start
	 * @return int
	 */
	public function hrStart() {
		return $this->HRstart;
	}

	/**
	 * Heart rate at end
	 * @return int
	 */
	public function hrEnd() {
		return $this->HRend;
	}

	/**
	 * Heart rate difference
	 * @return int A negative value indicates an increase
	 */
	public function hrDiff() {
		return $this->HRstart - $this->HRend;
	}
}
