<?php

declare(strict_types=1);

/**
 * This file contains class::DataBrowserMode
 * @package Runalyze\Parameter\Application
 */
namespace Runalyze\Parameter\Application;

/**
 * Data browser mode
 * @author Hannes Christiansen
 * @package Runalyze\Parameter\Application
 */
class DataBrowserMode extends \Runalyze\Parameter\Select
{
	/**
	 * Month
	 * @var string
	 */
	const WEEK = 'week';

	/**
	 * Week
	 * @var string
	 */
	const MONTH = 'month';

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct(self::WEEK, [
    'options' => [
		        self::WEEK => __('Week view'),
		        self::MONTH => __('Month view'),
		    ],
]);
	}

    public function showWeek(): bool {
		return $this->value() == self::WEEK;
	}

    public function showMonth(): bool {
		return $this->value() == self::MONTH;
	}
}
