<?php

/**
 * This file contains class::Dataview
 * @package Runalyze\View\RaceResult\Dataview
 */

namespace Runalyze\View\RaceResult;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Configuration;
use Runalyze\Model;
use Runalyze\View\Tooltip;
use SportFactory;

/**
 * Race result dataview
 * @author Hannes Christiansen
 * @author Michael pohl
 * @package Runalyze\View\RaceResult\Dataview
 */
class Dataview
{
    /**
     * @var \Runalyze\Model\RaceResult\Entity
     */
    protected $RaceResult;

    public function __construct(Model\RaceResult\Entity $RaceResult)
    {
        $this->RaceResult = $RaceResult;
    }

    /**
     * Get official distance
     * @param null|int $decimals can be null to load default decimals from config
     * @param bool $isTrack
     */
    public function officialDistance($decimals = null, $isTrack = false): string
    {
        $decimals ??= Configuration::ActivityView()->decimals();

        if ($this->RaceResult->officialDistance() > 0) {
            if ($isTrack) {
                return (new Distance($this->RaceResult->officialDistance()))->stringMeter();
            }

            return Distance::format($this->RaceResult->officialDistance(), true, $decimals);
        }

        return '';
    }

    /**
     * Official Duration
     */
    public function officialTime(): \Runalyze\Activity\Duration
    {
        return new Duration($this->RaceResult->officialTime());
    }

    /**
     * Get a string for the speed depending on sportid
     */
    public function pace(int $sportid): \Runalyze\Activity\Pace
    {
        return new Pace((float) $this->RaceResult->officialTime(), $this->RaceResult->officialDistance(), SportFactory::getSpeedUnitFor($sportid));
    }

    /**
     * Total place with tooltip "of x"
     * @return string
     */
    public function placementTotalWithTooltip()
    {
        return $this->placementWithTooltip(
            (int) $this->RaceResult->placeTotal(),
            (int) $this->RaceResult->participantsTotal(),
            __('of %u overall')
        );
    }

    /**
     * Ageclass place with tooltip "of x"
     */
    public function placementAgeClassWithTooltip(): string
    {
        return $this->placementWithTooltip(
            (int) $this->RaceResult->placeAgeclass(),
            (int) $this->RaceResult->participantsAgeclass(),
            __('of %u in your age group')
        );
    }

    /**
     * Gender place with tooltip "of x"
     */
    public function placementGenderWithTooltip(): string
    {
        return $this->placementWithTooltip(
            (int) $this->RaceResult->placeGender(),
            (int) $this->RaceResult->participantsGender(),
            __('of %u men/women')
        );
    }

    /**
     * Total place with "of" age group overall
     */
    public function placementTotalWithParticipants(): string
    {
        return $this->placementWithParticipants(
            (int) $this->RaceResult->placeTotal(),
            (int) $this->RaceResult->participantsTotal()
        );
    }

    /**
     * Ageclass place with "of" age group participants
     */
    public function placementAgeClassWithParticipants(): string
    {
        return $this->placementWithParticipants(
            (int) $this->RaceResult->placeAgeclass(),
            (int) $this->RaceResult->participantsAgeclass()
        );
    }

    /**
     * Gender place with "of" age group gender
     */
    public function placementGenderWithParticipants(): string
    {
        return $this->placementWithParticipants(
            (int) $this->RaceResult->placeGender(),
            (int) $this->RaceResult->participantsGender()
        );
    }

    /**
     * Place with tooltip "of x"
     * @param string $sprintfString must contain "%u"
     */
    public function placementWithTooltip(?int $place, ?int $participants, string $sprintfString, string $unknownString = '-'): string
    {
        if ($place === null) {
            return $unknownString;
        }

        $placeString = $place . '.';

        if ($participants !== null && $participants !== 0) {

            $Tooltip = new Tooltip(sprintf($sprintfString, $participants) . ' ' . sprintf(__('(first %u%%)'), 100 * $place / $participants));
            $Tooltip->wrapAround($placeString);
        }

        return $placeString;
    }

    /**
     * Place with "of" for total number of participants
     * @param string $unknownString string to display if place is unknown
     */
    protected function placementWithParticipants(?int $place, ?int $participants, string $unknownString = '-'): string
    {
        if ($place === null) {
            return $unknownString;
        }

        if ($participants === null) {
            return $place . '.';
        }

        return sprintf('%u. of %u', $place, $participants);
    }
}
