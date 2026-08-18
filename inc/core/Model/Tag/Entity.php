<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Tag
 */

namespace Runalyze\Model\Tag;

use Runalyze\Model;

/**
 * Tag entity
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Model\Tag
 */
class Entity extends Model\EntityWithID {
	/**
	 * Key: tag
	 * @var string
	 */
	const TAG = 'tag';



    /**
     * All properties
     * @return array<int, string>
     */
    static public function allDatabaseProperties(): array {
		return array(
		    self::TAG,
		);
	}

    /**
     * Properties
     */
    public function properties(): array {
		return static::allDatabaseProperties();
	}

	/**
	 * Tag
	 * @return string
	 */
	public function tag() {
		return $this->Data[self::TAG];
	}

}
