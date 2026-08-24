<?php

/**
 * This file contains the class of the RunalyzePluginStat "MonthlyStats".
 * @package Runalyze\Plugins\Stats
 */
$PLUGINKEY = 'RunalyzePluginStat_MonthlyStats';

use Runalyze\Configuration;

/**
 * Class: RunalyzePluginStat_MonthlyStats
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Stats
 */
class RunalyzePluginStat_MonthlyStats extends PluginStat
{
    final public function name(): string {
		return __('Monthly Stats');
	}

    final public function description(): string {
		return sprintf(__('How many %s/hours did you do per month'), Configuration::General()->distanceUnitSystem()->distanceUnit());
	}

	/**
	 * @see PluginStat::displayContent()
	 */
	protected function displayContent() {
		// deprecated, see Runalyze\Bundle\CoreBundle\Controller\AbstractPluginsAwareController::getResponseForMonthlyStats
	}
}
