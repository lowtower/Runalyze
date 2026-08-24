<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Type
 */
namespace Runalyze\Model\Type;

use Runalyze\Model;

/**
 * Type entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Type
 */
class Entity extends Model\EntityWithID
{
	/**
	 * Key: name
	 * @var string
	 */
	const NAME = 'name';

	/**
	 * Key: short display
	 * @var string
	 */
	const ABBREVIATION = 'abbr';

	/**
	 * Key: sport id
	 * @var string
	 */
	const SPORTID = 'sportid';

	/**
	 * Key: short mode
	 * @var string
	 */
	const SHORT = 'short';

	/**
	 * Key: avg. heart rate
	 * @var string
	 */
	const HR_AVG = 'hr_avg';

	/**
	 * Key: quality session
	 * @var string
	 */
	const QUALITY_SESSION = 'quality_session';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array {
		return [
		    self::NAME,
		    self::ABBREVIATION,
		    self::SPORTID,
		    self::SHORT,
		    self::HR_AVG,
		    self::QUALITY_SESSION,
		];
	}

    /**
     * Properties
     */
    public function properties(): array {
		return static::allDatabaseProperties();
	}

	/**
	 * @return string
	 */
	public function name() {
		return $this->Data[self::NAME];
	}

	/**
	 * @return string
	 */
	public function abbreviation() {
		return $this->Data[self::ABBREVIATION];
	}

	/**
	 * @return int
	 */
	public function sportid() {
		return $this->Data[self::SPORTID];
	}

    /**
     * Uses short mode?
     */
    public function usesShortMode(): bool {
		return $this->Data[self::SHORT] == 1;
	}

    /**
     * Inherits short mode from sport type?
     */
    public function inheritsShortMode(): bool {
		return $this->Data[self::SHORT] == 2;
	}

	/**
	 * Avg. heart rate
	 * @return int
	 */
	public function hrAvg() {
		return $this->Data[self::HR_AVG];
	}

    /**
     * Is quality session?
     */
    public function isQualitySession(): bool {
		return $this->Data[self::QUALITY_SESSION] == 1;
	}
}
