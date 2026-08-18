<?php

/**
 * This file contains class::Route
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Route
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Route extends AbstractKey
{
	/** @var int */
	const DEFAULT_CUT = 20;

	/** @var string */
	const ROUTE_NAME_KEY = 'route_name';

	/**
	 * Enum id
	 * @return int
	 */
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::ROUTE;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column(): string
	{
		return '';
	}

	/**
	 * @return bool
	 */
	public function requiresJoin(): bool
	{
		return true;
	}

	/**
	 * @return array array('column' => '...', 'join' => 'LEFT JOIN ...', 'field' => '`x`.`y`)
	 */
	public function joinDefinition()
	{
		return array(
    'column' => self::ROUTE_NAME_KEY,
		    'join' => 'LEFT JOIN `'.PREFIX.'route` AS `route` ON `t`.`routeid` = `route`.id',
		    'field' => '`route`.`name` AS `'.self::ROUTE_NAME_KEY.'`'
);
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label(): string
	{
		return __('Route');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description(): string
	{
		return sprintf(
			__('Route names are automatically cut after %u characters.'),
			self::DEFAULT_CUT
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		if ($context->hasData(self::ROUTE_NAME_KEY) && $context->data(self::ROUTE_NAME_KEY) != '') {
			return \Helper::Cut(
				$context->data(self::ROUTE_NAME_KEY),
				self::DEFAULT_CUT
			);
		}

		return '';
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass(): string
	{
		return 'small l';
	}
}
