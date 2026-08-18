<?php

/**
 * This file contains class::Tags
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Tags
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Tags extends AbstractKey
{
	/** @var string */
	const CONCAT_TAGIDS_KEY = 'concat_tags';

    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::TAGS;
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return '';
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Tags');
	}

	public function requiresJoin(): bool
	{
		return true;
	}

	/**
	 * @return array array('column' => '...', 'join' => 'LEFT JOIN ...', 'field' => '`x`.`y`)
	 */
	public function joinDefinition(): array
	{
		return array(
    'column' => self::CONCAT_TAGIDS_KEY,
		    'join' => 'LEFT JOIN `'.PREFIX.'activity_tag` AS `atag` ON `t`.`id` = `atag`.activityid',
		    'field' => "GROUP_CONCAT(`atag`.`tagid` SEPARATOR ',') AS `".self::CONCAT_TAGIDS_KEY.'`'
);
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		$string = '';

		if ($context->hasData(self::CONCAT_TAGIDS_KEY) && $context->data(self::CONCAT_TAGIDS_KEY) != '') {
			$ids = explode(',', $context->data(self::CONCAT_TAGIDS_KEY));
			$Factory = new \Runalyze\Model\Factory(\SessionAccountHandler::getId());

			foreach (array_unique($ids) as $id) {
				$string .= '#'.$Factory->tag($id)->tag().' ';
			}
		}

		return $string;
	}

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
	{
		return 'small';
	}
}
