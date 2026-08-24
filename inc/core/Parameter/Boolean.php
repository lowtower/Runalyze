<?php

declare(strict_types=1);

/**
 * This file contains class::Boolean
 * @package Runalyze\Parameter
 */
namespace Runalyze\Parameter;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class Boolean extends \Runalyze\Parameter
{
	/**
	 * @param string $valueAsString new value
	 */
	public function setFromString($valueAsString) {
		$this->set( ($valueAsString == 'true') );
	}

    public function valueAsString(): string {
		return ($this->value() ? 'true' : 'false');
	}
}
