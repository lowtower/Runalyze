<?php

/**
 * This file contains class::Stresscolor
 * @package Runalyze\View
 */

namespace Runalyze\View;

/**
 * Stresscolor
 *
 * @author Hannes Christiansen
 * @package Runalyze\View
 */
class Stresscolor {
	/**
	 * Value
	 * @var float
	 */
	protected $Value;

	/**
	 * @param float $value
	 */
	public function __construct($value = 0) {
		$this->setValue($value);
	}

	/**
	 * @param float $value
	 * @return \Runalyze\View\Stresscolor this-reference
	 */
	public function setValue($value): self {
		$this->Value = $value;

		return $this;
	}

	public function value(): int {
		if ($this->Value < 0) {
			return 0;
		}

		if ($this->Value > 100) {
			return 100;
		}

		return (int) $this->Value;
	}

	/**
	 * Scale value
	 *
	 * By default stress values are scaled in [0, 100].
	 * This method can be used to transform a value from a different scale to [0, 100].
	 *
	 * After scaling the value you may need to used the text-parameter for the string display.
	 *
	 * @param float $from
	 * @param float $to
	 * @return \Runalyze\View\Stresscolor this-reference
	 */
	public function scale($from, $to): self {
		$this->Value = 100 * ($this->Value - $from) / ($to - $from);

		return $this;
	}

	public function rgb(): string {
		$hex = dechex(200 - 2*$this->value());

		if (strlen($hex) === 1) {
			$hex = '0'.$hex;
		}

		return 'c8'.$hex.$hex;
	}

    /**
     * String representation
     *
     * This will wrap the text in a span-tag with the computated color.
     * If you used another scale, make sure to display the appropriate text.
     *
     * @param string $text [optional]
     * @codeCoverageIgnore
     */
    public function string($text = ''): string {
		if ($text == '') {
			$text = $this->Value;
		}

		return '<span style="color:#'.$this->rgb().';">'.$text.'</span>';
	}
}
