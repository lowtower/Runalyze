<?php

declare(strict_types=1);

/**
 * This file contains class::Unknown
 * @package Runalyze\View\Icon\Weather
 */
namespace Runalyze\View\Icon\Weather;

/**
 * Weather icon: Unknown
 * @author Hannes Christiansen
 * @package Runalyze\View\Icon\„eather
 */
class Unknown extends \Runalyze\View\Icon\WeatherIcon
{
	/**
	 * Display
	 */
	protected function setLayer() {

	}

    public function code(): string {
		return '<i class="weather"></i>';
	}
}
