<?php

/**
 * This file contains class::MainEquipment
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Request;
use Runalyze\Dataset\Context;
use SearchLink;

/**
 * Dataset key: Main Equipment
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class MainEquipment extends AbstractEquipment
{
    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::MAIN_EQUIPMENT;
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Main Equipment');
    }

    /**
     * @codeCoverageIgnore
     */
    public function shortLabel(): string
    {
        return __('Equipment');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return __('You can choose a main equipment type for each sport in your sport configuration. '
                . 'Equipment objects of this type will be shown with their names.');
    }

    /**
     * Get string to display this dataset value
     */
    public function stringFor(Context $context): string
    {
        if ($context->hasData(parent::CONCAT_EQUIPMENT_KEY) && $context->data(parent::CONCAT_EQUIPMENT_KEY) != '') {
            $ids = explode(',', $context->data(parent::CONCAT_EQUIPMENT_KEY));
            $Factory = new \Runalyze\Model\Factory(\SessionAccountHandler::getId());
            $mainTypeID = $context->sport()
                ->mainEquipmentTypeID();
            $names = [];

            foreach (array_unique($ids) as $id) {
                $Equipment = $Factory->equipment((int) $id);

                if ($Equipment->typeid() === $mainTypeID) {
                    $names[] = Request::isOnSharedPage() ? $Factory->equipment((int) $id)->name() : SearchLink::to('equipmentid', (int) $id, $Factory->equipment((int) $id)->name());
                }
            }

            return implode(', ', $names);
        }

        return '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small nowrap';
    }
}
