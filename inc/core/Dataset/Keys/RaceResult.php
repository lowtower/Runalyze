<?php

declare(strict_types=1);

/**
 * This file contains class::RaceResult
 * @package Runalyze
 */
namespace Runalyze\Dataset\Keys;

use Runalyze\Activity;
use Runalyze\Dataset\Context;
use Runalyze\Model;
use Runalyze\View;

/**
 * Dataset key: Race Result
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Dataset\Keys
 */
class RaceResult extends AbstractKey
{
    /**
     * Enum id
     */
    public function id(): int
	{
		return \Runalyze\Dataset\Keys::RACE_RESULT;
	}

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
	{
		return __('Race result');
	}

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
	{
		return '';
	}

    /**
     * Database key
     */
    public function column(): string
	{
		return '';
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
		return [
    'column' => 'raceresult_placements',
		    'join' => 'LEFT JOIN `' . PREFIX . 'raceresult` AS `rrd` ON `rrd`.`activity_id` = `t`.id',
		    'field' => '`rrd`.`name` as `race_name`, `rrd`.`official_time`, `rrd`.`official_distance`, `rrd`.`place_total`, `rrd`.`place_gender`, `rrd`.`place_ageclass`, `rrd`.`participants_total`, `rrd`.`participants_ageclass`, `rrd`.`participants_gender`',
];
	}

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
	{
		return __('Your placements at races');
	}

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
	{
		switch ($context->activity()->id()) {
			case 0:
				return '';
			case -1:
				return $this->FakeString();
			default:

		if ($context->hasData('official_distance')) {
			$RaceResult = new Model\RaceResult\Entity([
    Model\RaceResult\Entity::NAME => $context->data('race_name'),
			    Model\RaceResult\Entity::OFFICIAL_TIME => $context->data('official_time'),
			    Model\RaceResult\Entity::OFFICIAL_DISTANCE => $context->data('official_distance'),
			    Model\RaceResult\Entity::PLACE_TOTAL => $context->data('place_total', true),
			    Model\RaceResult\Entity::PLACE_GENDER => $context->data('place_gender', true),
			    Model\RaceResult\Entity::PLACE_AGECLASS => $context->data('place_ageclass', true),
			    Model\RaceResult\Entity::PARTICIPANTS_TOTAL => $context->data('participants_total', true),
			    Model\RaceResult\Entity::PARTICIPANTS_GENDER => $context->data('participants_gender', true),
			    Model\RaceResult\Entity::PARTICIPANTS_AGECLASS => $context->data('participants_ageclass', true),
]);

			$RaceResultView = new View\RaceResult\Dataview($RaceResult);

			$TooltipCode = '';

			if ($context->hasData('race_name') && $context->data('race_name') != '') {
				$TooltipCode .= $RaceResult->name() . '<br>';
			}

			$TooltipCode .= __('Official distance') . ': ' . $RaceResultView->officialDistance() . '<br>';
			$TooltipCode .= __('Official time') . ': ' . $RaceResultView->officialTime()->string(Activity\Duration::FORMAT_COMPETITION) . '<br>';

			if ($context->hasData('place_total')) {
				$TooltipCode .= __('Total') . ': ' . $RaceResultView->placementTotalWithParticipants() . '<br>';
			}

			if ($context->hasData('place_ageclass')) {
				$TooltipCode .= __('Age group') . ': ' . $RaceResultView->placementAgeClassWithParticipants() . '<br>';
			}

			if ($context->hasData('place_gender')) {
				$TooltipCode .= __('Gender') . ': ' . $RaceResultView->placementGenderWithParticipants() . '<br>';
			}

			$Icon = new View\Icon('fa-trophy');
			$Icon->setTooltip($TooltipCode);
            if (! \Request::isOnSharedPage()) {
                return '<a class="window" href="my/raceresult/' . $context->activity()->id() . '">' . $Icon->code() . '</a>';
            }

            return $Icon->code();
		}

		return '';
		}
	}

    /**
     * @codeCoverageIgnore
     */
    protected function fakeString(): string
	{
			$RaceResult = new Model\RaceResult\Entity([
    Model\RaceResult\Entity::NAME => __('Race name'),
			    Model\RaceResult\Entity::OFFICIAL_TIME => 2400,
			    Model\RaceResult\Entity::OFFICIAL_DISTANCE => 10.00,
			    Model\RaceResult\Entity::PLACE_TOTAL => 4,
			    Model\RaceResult\Entity::PLACE_GENDER => 5,
			    Model\RaceResult\Entity::PLACE_AGECLASS => 2,
			    Model\RaceResult\Entity::PARTICIPANTS_TOTAL => 2000,
			    Model\RaceResult\Entity::PARTICIPANTS_GENDER => 300,
			    Model\RaceResult\Entity::PARTICIPANTS_AGECLASS => 100,
]);

			$RaceResultView = new View\RaceResult\Dataview($RaceResult);

			$TooltipCode = $RaceResult->name() . '<br>';

			$TooltipCode .= __('Official distance') . ': ' . $RaceResultView->officialDistance() . '<br>';
			$TooltipCode .= __('Official time') . ': ' . $RaceResultView->officialTime()->string(Activity\Duration::FORMAT_COMPETITION) . '<br>';
			$TooltipCode .= __('Total') . ': ' . $RaceResultView->placementTotalWithParticipants() . '<br>';
			$TooltipCode .= __('Age group') . ': ' . $RaceResultView->placementAgeClassWithParticipants() . '<br>';
			$TooltipCode .= __('Gender') . ': ' . $RaceResultView->placementGenderWithParticipants() . '<br>';

			$Icon = new View\Icon('fa-trophy');
			$Icon->setTooltip($TooltipCode);

			return $Icon->code();

	}
}
