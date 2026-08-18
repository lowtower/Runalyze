<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\EquipmentType
 */

namespace Runalyze\Model\EquipmentType;

use Runalyze\Model;

/**
 * Type entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\EquipmentType
 */
class Entity extends Model\EntityWithID {
	/**
	 * @var int enum
	 */
	const CHOICE_SINGLE = 0;

	/**
	 * @var int enum
	 */
	const CHOICE_MULTIPLE = 1;

	/**
	 * Key: name
	 * @var string
	 */
	const NAME = 'name';

	/**
	 * Key: input
	 * @var string
	 */
	const INPUT = 'input';

	/**
	 * Key: max distance
	 * @var string
	 */
	const MAX_KM = 'max_km';

	/**
	 * Key: max duration
	 * @var string
	 */
	const MAX_TIME = 'max_time';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array {
		return array(
		    self::NAME,
		    self::INPUT,
		    self::MAX_KM,
		    self::MAX_TIME
		);
	}

	/**
	 * Properties
	 * @return array
	 */
	public function properties() {
		return static::allDatabaseProperties();
	}

	/**
	 * Synchronize
	 */
	public function synchronize() {
		parent::synchronize();

		$this->ensureAllNumericValues();
	}

	/**
	 * Ensure that numeric fields get numeric values
	 */
	protected function ensureAllNumericValues() {
		$this->ensureNumericValue(array(
		    self::INPUT,
		    self::MAX_KM,
		    self::MAX_TIME
		));
	}

	/**
	 * Name
	 * @return string
	 */
	public function name() {
		return $this->Data[self::NAME];
	}

    /**
     * Allows multiple values
     */
    public function allowsMultipleValues(): bool {
		return ($this->Data[self::INPUT] == self::CHOICE_MULTIPLE);
	}

	/**
	 * Maximal distance
	 * @return int [km]
	 */
	public function maxDistance() {
		return $this->Data[self::MAX_KM];
	}

    /**
     * Is a maximal distance set?
     */
    public function hasMaxDistance(): bool {
		return ($this->Data[self::MAX_KM] > 0);
	}

	/**
	 * Maximal duration
	 * @return int [s]
	 */
	public function maxDuration() {
		return $this->Data[self::MAX_TIME];
	}

    /**
     * Is a maximal duration set?
     */
    public function hasMaxDuration(): bool {
		return ($this->Data[self::MAX_TIME] > 0);
	}
}
