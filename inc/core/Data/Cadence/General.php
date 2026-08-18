<?php

/**
 * This file contains class::General
 * @package Runalyze\Data\Cadence
 */

namespace Runalyze\Data\Cadence;

/**
 * General cadence in rounds per minute
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Cadence
 */
class General extends AbstractCadence {
	/**
	 * Label
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string {
		return __('Cadence');
	}

	/**
	 * Unit as string
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function unitAsString(): string {
		return 'rpm';
	}

	/**
	 * Explanation for unit
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function unitExplanation(): string {
		return __('rpm = rotations per minute');
	}

	/**
	 * Formular unit
	 * @return string enum
	 * @codeCoverageIgnore
	 */
	public function formularUnit() {
		return \FormularUnit::$RPM;
	}
}
