<?php

declare(strict_types=1);

/**
 * This file contains class::HeartRateUnit
 * @package Runalyze\Parameter\Application
 */
namespace Runalyze\Parameter\Application;

/**
 * Heart rate unit
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class HeartRateUnit extends \Runalyze\Parameter\Select {
	/**
	 * BPM
	 * @var string
	 */
	const BPM = 'bpm';

	/**
	 * % HRmax
	 * @var string
	 */
	const HRMAX = 'hfmax';

	/**
	 * % HRreserve
	 * @var string
	 */
	const HRRESERVE = 'hfres';

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct(self::HRMAX, array(
    'options'		=> array(
		        self::BPM		=> __('absolute value'),
		        self::HRMAX		=> __('&#37; HRmax'),
		        self::HRRESERVE	=> __('&#37; HRreserve')
		    )
));
	}

    /**
     * Is bpm?
     */
    public function isBPM(): bool {
		return ($this->value() == self::BPM);
	}

    /**
     * Is HRmax?
     */
    public function isHRmax(): bool {
		return ($this->value() == self::HRMAX);
	}

    /**
     * Is HRreserve?
     */
    public function isHRreserve(): bool {
		return ($this->value() == self::HRRESERVE);
	}
}
