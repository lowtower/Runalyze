<?php

declare(strict_types=1);

/**
 * This file contains class::ElevationMethod
 * @package Runalyze\Parameter\Application
 */
namespace Runalyze\Parameter\Application;

/**
 * Elevation method
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class ElevationMethod extends \Runalyze\Parameter\Select
{
	/**
	 * None
	 * @var string
	 */
	const NONE = 'none';

	/**
	 * Threshold
	 * @var string
	 */
	const THRESHOLD = 'treshold';

	/**
	 * Douglas-Peucker
	 * @var string
	 */
	const DOUGLAS_PEUCKER = 'douglas-peucker';

	/**
	 * Reumann-Witkam
	 * @var string
	 */
	const REUMANN_WITKAM = 'reumann-witkamm';

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct(self::THRESHOLD, [
    'options' => [
		        self::NONE => __('no smoothing'),
		        self::THRESHOLD => __('Threshold method'),
		        self::DOUGLAS_PEUCKER => __('Douglas-Peucker-Algorithm'), //,
		        //self::REUMANN_WITKAM	=> __('Reumann-Witkam-Algorithm')
		    ],
]);
	}

    /**
     * Uses none
     */
    public function usesNone(): bool {
		return $this->value() == self::NONE;
	}

    /**
     * Uses: Threshold
     */
    public function usesThreshold(): bool {
		return $this->value() == self::THRESHOLD;
	}

    /**
     * Uses: Douglas-Peucker
     */
    public function usesDouglasPeucker(): bool {
		return $this->value() == self::DOUGLAS_PEUCKER;
	}

    /**
     * Uses: Reumann-Witkam
     */
    public function usesReumannWitkam(): bool {
		return $this->value() == self::REUMANN_WITKAM;
	}
}
