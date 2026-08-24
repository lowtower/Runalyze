<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\HRV
 */
namespace Runalyze\Model\HRV;

use Runalyze\Model;

/**
 * HRV entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\HRV
 */
class Entity extends Model\EntityWithID implements Model\Loopable
{
	/**
	 * Key: acitivityid
	 * @var string
	 */
	const ACTIVITYID = 'activityid';

	/**
	 * Key: data
	 * @var string
	 */
	const DATA = 'data';

    /**
     * @return array<int, string>
     */
    public static function allProperties(): array {
		return [
		    self::ACTIVITYID,
		    self::DATA,
		];
	}

    /**
     * Properties
     */
    public function properties(): array {
		return static::allProperties();
	}

    /**
     * Is the property an array?
     * @param string $key
     */
    public function isArray($key): bool {
		return ($key == self::DATA);
	}

    /**
     * Can be null?
     * @param string $key
     */
    protected function canBeNull($key): bool
    {
        return $key === self::DATA;
    }

    /**
     * Ignore a key while checking for emptiness
     * @param string $key enum
     */
    protected function ignoreNonEmptyValue($key): bool {
		return ($key == self::ACTIVITYID);
	}

	/**
	 * Value at
	 *
	 * Remark: This method may throw index offsets.
	 * @param int $index
	 * @param int $key string
	 * @return mixed
	 */
	public function at($index, $key) {
		return $this->Data[$key][$index];
	}

	/**
	 * Get activitiy id
	 * @return int
	 */
	public function activityID() {
		return $this->Data[self::ACTIVITYID];
	}

	/**
	 * @return array unit: [ms]
	 */
	public function data() {
		return $this->Data[self::DATA];
	}
}
