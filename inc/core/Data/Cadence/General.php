<?php

declare(strict_types=1);

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
     * @codeCoverageIgnore
     */
    public function label(): string {
		return __('Cadence');
	}

    /**
     * Unit as string
     * @codeCoverageIgnore
     */
    public function unitAsString(): string {
		return 'rpm';
	}

    /**
     * Explanation for unit
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
