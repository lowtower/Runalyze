<?php

declare(strict_types=1);

/**
 * This file contains class::EntityWithID
 * @package Runalyze\Model
 */
namespace Runalyze\Model;

/**
 * Abstract object with ID
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class EntityWithID extends Entity
{
	/**
	 * ID
	 * @var int
	 */
	protected $ID;

    /**
     * Construct
     */
    public function __construct(array $data = []) {
		parent::__construct($data);

		if (isset($data['id'])) {
			$this->ID = $data['id'];
		}
	}

	/**
	 * Has ID?
	 * @return bool
	 */
	public function hasID() {
		return $this->ID !== null;
	}

	/**
	 * @return int
	 */
	public function id() {
		return $this->ID;
	}

	/**
	 * Use this method only if you know what you're doing!
	 * @param int $id
	 */
	public function setID($id) {
		$this->ID = $id;
	}
}
