<?php

/**
 * This file contains class::LapsComputed
 * @package Runalyze\View\Activity\Plot
 */

namespace Runalyze\View\Activity\Plot;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Configuration;
use Runalyze\Model\Trackdata;
use Runalyze\View\Activity;

/**
 * Plot for: computed laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Plot
 */
class LapsComputed extends Laps
{
    /**
     * Set key and title for this plot
     */
    protected function setKey()
    {
        $this->key = 'laps_computed';
        $this->title = __('Computed Laps');
    }

    protected function loadData(Activity\Context $context)
    {
        if (! $context->trackdata()->has(Trackdata\Entity::DISTANCE) || ! $context->trackdata()->has(Trackdata\Entity::TIME)) {
            $this->Plot->raiseError(__('No GPS-data available. Can\\\'t compute laps.'));
            return;
        }

        $laps = $this->computeRounds($context);
        $num = $laps->num();

        foreach ($laps->objects() as $i => $lap) {
            $km = $i + 1;
            if ($num < 30) {
                $label = $km;
            } elseif ($num < 50) {
                $label = ($km % 2 === 1 && $km > 0) ? $km : '';
            } elseif ($num < 100) {
                $label = ($km % 5 === 0 && $km > 0) ? $km : '';
            } else {
                $label = ($km % 10 === 0 && $km > 0) ? $km : '';
            }

            $this->Labels[$i] = [$i, $label];
            $this->Data[$i] = $this->barValue(PaceFormatter::secondsPerKilometer($lap->duration()->seconds(), $lap->distance()->kilometer()));
        }

        $this->achievedPace = PaceFormatter::secondsPerKilometer($context->activity()->duration(), $context->activity()->distance());
        $this->Plot->Data[] = [
            'label' => $this->title,
            'data' => $this->Data,
        ];
    }

    protected function computeRounds(Activity\Context $context): \Runalyze\Data\Laps\Laps
    {
        $singleDistance = Configuration::General()->distanceUnitSystem()->distanceToKmFactor();
        $totalDistance = $context->trackdata()
            ->totalDistance();

        if ($totalDistance < 2 * $singleDistance) {
            $Distances = [$singleDistance];
        } else {
            try {
                $Distances = range($singleDistance, $totalDistance, $singleDistance);
            } catch (\ValueError) {
                $Distances = [$singleDistance];
            }
        }

        $laps = new \Runalyze\Data\Laps\Laps();
        $laps->calculateFrom($Distances, $context->trackdata());

        return $laps;
    }
}
