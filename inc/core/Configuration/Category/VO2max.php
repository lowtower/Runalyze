<?php

namespace Runalyze\Configuration\Category;

use Ajax;
use FormularUnit;
use Helper;
use Runalyze\Configuration\Fieldset;
use Runalyze\Configuration\Messages;
use Runalyze\Parameter\Boolean;
use Runalyze\Parameter\FloatingPoint;
use Runalyze\Parameter\Integer;
use Runalyze\Parameter\Textline;

class VO2max extends \Runalyze\Configuration\Category
{
    /**
     * @var bool Flag: recalculation triggered?
     */
    private static $TRIGGERED = false;

    protected function key(): string
    {
        return 'vo2max';
    }

    protected function createHandles()
    {
        $this->createHandle('VO2MAX_DAYS', new Integer(30));
        $this->createHandle('VO2MAX_MANUAL_CORRECTOR', new FloatingPoint(null, [
            'min' => 0.50,
            'max' => 2.00,
            'null' => true,
        ]));
        $this->createHandle('VO2MAX_MANUAL_VALUE', new Textline(''));

        $this->createHandle('VO2MAX_USE_CORRECTION_FOR_ELEVATION', new Boolean(false));
        $this->createHandle('VO2MAX_CORRECTION_POSITIVE_ELEVATION', new Integer(2));
        $this->createHandle('VO2MAX_CORRECTION_NEGATIVE_ELEVATION', new Integer(-1));
    }

    /**
     * @return int
     */
    public function days()
    {
        return $this->get('VO2MAX_DAYS');
    }

    /**
     * @return float|null
     */
    public function manualFactor()
    {
        return $this->get('VO2MAX_MANUAL_CORRECTOR');
    }

    public function useManualFactor(): bool
    {
        return $this->get('VO2MAX_MANUAL_CORRECTOR') !== null;
    }

    public function manualValue(): float
    {
        return (float) Helper::CommaToPoint($this->get('VO2MAX_MANUAL_VALUE'));
    }

    public function useManualValue(): bool
    {
        return $this->manualValue() > 0.0;
    }

    /**
     * @return bool
     */
    public function useElevationCorrection()
    {
        return $this->get('VO2MAX_USE_CORRECTION_FOR_ELEVATION');
    }

    /**
     * @return int
     */
    public function correctionForPositiveElevation()
    {
        return $this->get('VO2MAX_CORRECTION_POSITIVE_ELEVATION');
    }

    /**
     * @return int
     */
    public function correctionForNegativeElevation()
    {
        return $this->get('VO2MAX_CORRECTION_NEGATIVE_ELEVATION');
    }

    protected function registerOnchangeEvents()
    {
        $this->handle('VO2MAX_DAYS')
            ->registerOnchangeEvent(self::class . '::triggerRecalculation');
        $this->handle('VO2MAX_DAYS')
            ->registerOnchangeFlag(Ajax::$RELOAD_ALL);

        $this->handle('VO2MAX_MANUAL_CORRECTOR')
            ->registerOnchangeEvent(self::class . '::triggerRecalculation');
        $this->handle('VO2MAX_MANUAL_CORRECTOR')
            ->registerOnchangeFlag(Ajax::$RELOAD_ALL);

        $this->handle('VO2MAX_MANUAL_VALUE')
            ->registerOnchangeFlag(Ajax::$RELOAD_PLUGINS);

        $this->handle('VO2MAX_USE_CORRECTION_FOR_ELEVATION')
            ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::useCleanup');
        $this->handle('VO2MAX_CORRECTION_POSITIVE_ELEVATION')
            ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::useCleanup');
        $this->handle('VO2MAX_CORRECTION_NEGATIVE_ELEVATION')
            ->registerOnchangeEvent(\Runalyze\Configuration\Messages::class . '::useCleanup');
    }

    public function Fieldset(): \Runalyze\Configuration\Fieldset
    {
        $Fieldset = new Fieldset(__('VO<sub>2</sub>max estimation'));

        $Fieldset->addHandle($this->handle('VO2MAX_DAYS'), [
            'label' => __('Time constant length for VO<sub>2</sub>max'),
            'tooltip' => __('Time constant length for VO<sub>2</sub>max rolling average'),
        ]);

        $Fieldset->addHandle($this->handle('VO2MAX_MANUAL_CORRECTOR'), [
            'label' => __('Manual correction factor'),
            'tooltip' => __('Manual correction factor (e.g. 0.9), if the automatic factor does not fit. Can be left empty.')
                . '<br>' . sprintf(__('Value must be between %s and %s.'), '0.50', '2.00'),
        ]);

        $Fieldset->addHandle($this->handle('VO2MAX_MANUAL_VALUE'), [
            'label' => __('Use fixed VO<sub>2</sub>max value'),
            'tooltip' => __('Fixed VO<sub>2</sub>max value (e.g. 55), if the estimation does not fit. Can be left empty.'),
        ]);

        $Fieldset->addHandle($this->handle('VO2MAX_USE_CORRECTION_FOR_ELEVATION'), [
            'label' => __('Adapt for elevation'),
            'tooltip' => __('The distance can be corrected by a formula from Peter Greif to adapt for elevation.'),
        ]);

        $Fieldset->addHandle($this->handle('VO2MAX_CORRECTION_POSITIVE_ELEVATION'), [
            'label' => __('Correction per positive elevation'),
            'tooltip' => __('Add for each meter upwards X meter to the distance.') . ' (' . __('Only for VO2max estimation') . ')',
            'unit' => FormularUnit::$M,
        ]);

        $Fieldset->addHandle($this->handle('VO2MAX_CORRECTION_NEGATIVE_ELEVATION'), [
            'label' => __('Correction per negative elevation'),
            'tooltip' => __('Add for each meter downwards X meter to the distance.') . ' (' . __('Only for VO2max estimation') . ')',
            'unit' => FormularUnit::$M,
        ]);

        return $Fieldset;
    }

    public static function triggerRecalculation()
    {
        if (! self::$TRIGGERED) {
            self::$TRIGGERED = true;

            $Data = \Runalyze\Configuration::Data();

            $oldValue = $Data->vo2maxShape();
            $newValue = $Data->recalculateVO2maxShape();

            Messages::addValueRecalculated(__('VO<sub>2</sub>max shape'), number_format($newValue, 1), number_format($oldValue, 1));
        }
    }
}
