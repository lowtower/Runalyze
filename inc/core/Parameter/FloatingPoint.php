<?php

/**
 * This file contains class::FloatingPoint
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

use Helper;

/**
 * FloatingPoint (prev. Float)
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class FloatingPoint extends \Runalyze\Parameter
{
	/**
	 * Options
	 * @var array
	 */
	protected $Options = [
    'min' => false,
	    'max' => false,
	    'null' => false,
];

	/**
	 * @param string $valueAsString new value
	 */
	public function setFromString($valueAsString) {
		if ($valueAsString == '' && $this->Options['null']) {
			$this->set(null);
		} else {
			$this->set((float) Helper::CommaToPoint($valueAsString));
		}
	}

	/**
	 * Set value
	 * @param mixed $value new value
	 */
	public function set($value) {
		if ($value === null && $this->Options['null']) {
			$value = null;
		} elseif ($this->Options['min'] !== false && $value < $this->Options['min']) {
			$value = $this->Options['min'];
		} elseif ($this->Options['max'] !== false && $value > $this->Options['max']) {
			$value = $this->Options['max'];
		}

		parent::set($value);
	}

    public function valueAsString(): string {
		$value = $this->value();

		if ($value === null && $this->Options['null']) {
			return '';
		}

		return (string) $value;
	}

	public function isEmpty(): bool {
		return ($this->value() === null);
	}
}
