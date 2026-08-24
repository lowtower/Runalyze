<?php

/**
 * This file contains the class of the RunalyzePluginPanel "Equipment".
 * @package Runalyze\Plugins\Panels
 */
$PLUGINKEY = 'RunalyzePluginPanel_Equipment';

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Model;

/**
 * Class: RunalyzePluginPanel_Equipment
 *
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Panels
 */
class RunalyzePluginPanel_Equipment extends PluginPanel
{
    /**
     * @var array
     */
    protected $AllTypes = [];

    final public function name(): string
    {
        return __('Equipment');
    }

    final public function description(): string
    {
        return __('Display statistics for your equipment.');
    }

    protected function initConfiguration()
    {
        $this->AllTypes = DB::getInstance()->query('SELECT `id`, `name` FROM `' . PREFIX . 'equipment_type` WHERE `accountid`="' . SessionAccountHandler::getId() . '" ORDER BY `name` ASC')->fetchAll();
        $Options = [];

        foreach ($this->AllTypes as $data) {
            $Options[$data['id']] = $data['name'];
        }

        $Types = new PluginConfigurationValueSelect('type', __('Equipment type to display'));
        $Types->setOptions($Options);

        $Configuration = new PluginConfiguration($this->id());
        $Configuration->addValue($Types);

        if (isset($_GET['type']) && isset($Options[$_GET['type']])) {
            $Configuration->object('type')
                ->setValue($_GET['type']);
            $Configuration->update('type');
            Cache::delete(PluginConfiguration::CACHE_KEY);
        }

        $this->setConfiguration($Configuration);
    }

    /**
     * Method for getting the right symbol(s)
     */
    protected function getRightSymbol(): string
    {
        $CurrentType = '';
        $TypeLinks = [];

        foreach ($this->AllTypes as $Type) {
            $active = $Type['id'] == (int) $this->Configuration()->value('type');
            $TypeLinks[] = '<li' . ($active ? ' class="active"' : '') . '>' . Ajax::link($Type['name'], 'panel-' . $this->id(), Plugin::$DISPLAY_URL . '/' . $this->id() . '?type=' . $Type['id']) . '</li>';

            if ($active) {
                $CurrentType = $Type['name'];
            }
        }

        $Links = '<li class="with-submenu"><span class="link">' . $CurrentType . '</span>';
        $Links .= '<ul class="submenu">' . implode('', $TypeLinks) . '</ul>';
        $Links .= '</li>';
        $Links .= '<li>' . Ajax::window('<a href="my/equipment/overview" ' . Ajax::tooltip('', __('Add/Edit equipment'), true, true) . '>' . Icon::$ADD . '</a>') . '</li>';
        $Links .= '<li>' . Ajax::window('<a href="my/equipment/category/' . (int) $this->Configuration()->value('type') . '/table" ' . Ajax::tooltip('', __('Show all equipment'), true, true) . '>' . Icon::$TABLE . '</a>', 'big') . '</li>';

        return '<ul>' . $Links . '</ul>';
    }

    /**
     * @see PluginPanel::displayContent()
     */
    protected function displayContent()
    {
        $Factory = new Model\Factory(SessionAccountHandler::getId());
        $EquipmentType = $Factory->equipmentType((int) $this->Configuration()->value('type'));

        if ($EquipmentType->isEmpty()) {
            echo HTML::warning(__('Please choose an equipment type in the plugin configuration.'));
            return;
        }

        echo $this->getStyle();
        echo '<div id="equipment">';

        $inuse = true;
        $this->showListFor($EquipmentType, $inuse);

        echo '</div>';

        if (! $inuse) {
            echo Ajax::toggle('<a class="right" href="#equipment" name="equipment">' . __('Show/Hide unused equipment') . '</a>', 'hiddenequipment');
        }

        echo HTML::clearBreak();
    }

    /**
     * @param bool $inuse
     */
    protected function showListFor(Model\EquipmentType\Entity $EquipmentType, &$inuse)
    {
        $max = 0;
        $showDistance = $EquipmentType->hasMaxDistance();
        $hasMaxDuration = $showDistance || $EquipmentType->hasMaxDuration();
        $allEquipment = DB::getInstance()->query('SELECT * FROM `' . PREFIX . 'equipment` WHERE `typeid`="' . $EquipmentType->id() . '" AND `accountid`="' . SessionAccountHandler::getId() . '" ORDER BY ISNULL(`date_end`) DESC, `distance` DESC')->fetchAll();

        foreach ($allEquipment as $data) {
            $Object = new Model\Equipment\Entity($data);
            $Distance = new Distance($Object->totalDistance());
            $Duration = new Duration($Object->duration());

            if ($inuse && ! $Object->isInUse()) {
                echo '<div id="hiddenequipment" style="display:none;">';
                $inuse = false;
            }

            if ($max == 0) {
                $max = $Object->duration();
            }

            echo '<p style="position:relative;">
				<span class="right">' . ($showDistance ? $Distance->string() : $Duration->string()) . '</span>
				<strong>' . SearchLink::to('equipmentid', $Object->id(), $Object->name()) . '</strong>
				' . $this->getUsageImage(
                $showDistance
                        ? $Object->totalDistance() / $EquipmentType->maxDistance()
                        : $Object->duration() / ($hasMaxDuration ? $EquipmentType->maxDuration() : max(1, $max))
            ) . '
			</p>';
        }

        if (empty($allEquipment)) {
            echo HTML::em(__("You don't have any equipment"));
        }

        if (! $inuse) {
            echo '</div>';
        }
    }

    protected function getStyle(): string
    {
        return '<style type="text/css">.equipment-usage { position: absolute; bottom: 0; left: 0; background-image:url(assets/images//damage.png); background-position:left center; height: 2px; max-width: 100%; }</style>';
    }

    /**
     * Get shoe usage image
     * @param float $percentage [0.0 .. 1.0]
     */
    protected function getUsageImage($percentage): string
    {
        return '<span class="equipment-usage" style="width:' . round($percentage * 330) . 'px;"></span>';
    }
}
