<?php

/**
 * This file contains class::General
 * @package Runalyze\Configuration\Category
 */

namespace Runalyze\Configuration\Category;

use Ajax;
use Runalyze\Configuration\Fieldset;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Parameter\Application\EnergyUnit;
use Runalyze\Parameter\Application\HeartRateUnit;
use Runalyze\Parameter\Application\TemperatureUnit;
use Runalyze\Parameter\Application\WeekStart;
use Runalyze\Parameter\Application\WeightUnit;
use Runalyze\Parameter\SelectRow;

/**
 * Configuration category: General
 * @author Hannes Christiansen
 * @package Runalyze\Configuration\Category
 */
class General extends \Runalyze\Configuration\Category
{
    /**
     * Internal key
     */
    protected function key(): string
    {
        return 'general';
    }

    protected function createHandles()
    {
        $this->createWeekStart();
        $this->createDistanceUnitSystem();
        $this->createWeightUnit();
        $this->createEnergyUnit();
        $this->createTemperatureUnit();
        $this->createHeartRateUnit();
        $this->createMainSport();
        $this->createRunningSport();
    }

    /**
     * Create: Beginning of the week
     */
    protected function createWeekStart()
    {
        $this->createHandle('WEEK_START', new WeekStart());
    }

    public function weekStart(): \Runalyze\Parameter&WeekStart
    {
        /** @var WeekStart $parameter */
        $parameter = $this->object('WEEK_START');
        return $parameter;
    }

    /**
     * Create: DISTANCE_UNIT_SYSTEM
     */
    protected function createDistanceUnitSystem()
    {
        $this->createHandle('DISTANCE_UNIT_SYSTEM', new DistanceUnitSystem());
    }

    /**
     * Unit system for distances
     */
    public function distanceUnitSystem(): \Runalyze\Parameter&DistanceUnitSystem
    {
        /** @var DistanceUnitSystem $parameter */
        $parameter = $this->object('DISTANCE_UNIT_SYSTEM');
        return $parameter;
    }

    /**
     * Create: WeightUnit
     */
    protected function createWeightUnit()
    {
        $this->createHandle('WEIGHT_UNIT', new WeightUnit());
    }

    public function weightUnit(): \Runalyze\Parameter&WeightUnit
    {
        /** @var WeightUnit $parameter */
        $parameter = $this->object('WEIGHT_UNIT');
        return $parameter;
    }

    /**
     * Create: EnergyUnit
     */
    protected function createEnergyUnit()
    {
        $this->createHandle('ENERGY_UNIT', new EnergyUnit());
    }

    public function energyUnit(): \Runalyze\Parameter&EnergyUnit
    {
        /** @var EnergyUnit $parameter */
        $parameter = $this->object('ENERGY_UNIT');
        return $parameter;
    }

    /**
     * Create: TemperatureUnit
     */
    protected function createTemperatureUnit()
    {
        $this->createHandle('TEMPERATURE_UNIT', new TemperatureUnit());
    }

    public function temperatureUnit(): \Runalyze\Parameter&TemperatureUnit
    {
        /** @var TemperatureUnit $parameter */
        $parameter = $this->object('TEMPERATURE_UNIT');
        return $parameter;
    }

    /**
     * Create: HEART_RATE_UNIT
     */
    protected function createHeartRateUnit()
    {
        $this->createHandle('HEART_RATE_UNIT', new HeartRateUnit());
    }

    public function heartRateUnit(): \Runalyze\Parameter&HeartRateUnit
    {
        /** @var HeartRateUnit $parameter */
        $parameter = $this->object('HEART_RATE_UNIT');
        return $parameter;
    }

    /**
     * Create: MAINSPORT
     */
    protected function createMainSport()
    {
        $this->createHandle('MAINSPORT', new SelectRow('1', [
            'table' => 'sport',
            'column' => 'name',
        ]));
    }

    public function mainSport(): int
    {
        return (int) $this->get('MAINSPORT');
    }

    /**
     * Create: RUNNINGSPORT
     */
    protected function createRunningSport()
    {
        $this->createHandle('RUNNINGSPORT', new SelectRow('1', [
            'table' => 'sport',
            'column' => 'name',
        ]));
    }

    public function runningSport(): int
    {
        return (int) $this->get('RUNNINGSPORT');
    }

    protected function registerOnchangeEvents()
    {
        $this->handle('DISTANCE_UNIT_SYSTEM')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_ALL);
        $this->handle('ENERGY_UNIT')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_ALL);
        $this->handle('DISTANCE_UNIT_SYSTEM')
            ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::adjustPacesInSportsConfiguration');
        $this->handle('WEIGHT_UNIT')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_PLUGINS);
        $this->handle('TEMPERATURE_UNIT')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_DATABROWSER);
        $this->handle('HEART_RATE_UNIT')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_DATABROWSER);
        $this->handle('WEEK_START')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_PAGE);
        $this->handle('MAINSPORT')
            ->registerOnchangeFlag((string) Ajax::$RELOAD_PAGE);
    }

    public function Fieldset(): \Runalyze\Configuration\Fieldset
    {
        $Fieldset = new Fieldset(__('General settings'));

        $Fieldset->addHandle($this->handle('WEEK_START'), [
            'label' => __('Beginning of the week'),
        ]);

        $Fieldset->addHandle($this->handle('DISTANCE_UNIT_SYSTEM'), [
            'label' => __('Unit system for distances'),
            'tooltip' => __('Changing the unit system for distances does not change pace units. You have to adjust them in sports configuration.'),
        ]);

        $Fieldset->addHandle($this->handle('WEIGHT_UNIT'), [
            'label' => __('Weight unit'),
        ]);

        $Fieldset->addHandle($this->handle('ENERGY_UNIT'), [
            'label' => __('Energy unit'),
        ]);

        $Fieldset->addHandle($this->handle('TEMPERATURE_UNIT'), [
            'label' => __('Temperature unit'),
        ]);

        $Fieldset->addHandle($this->handle('HEART_RATE_UNIT'), [
            'label' => __('Heart rate unit'),
        ]);

        $Fieldset->addHandle($this->handle('MAINSPORT'), [
            'label' => __('Main sport'),
        ]);

        return $Fieldset;
    }
}
