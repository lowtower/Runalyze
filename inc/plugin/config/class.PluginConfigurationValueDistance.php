<?php

/**
 * This file contains class::PluginConfigurationValueDistance
 * @package Runalyze\Plugin
 */

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Plugin configuration value: float [km|miles]
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueDistance extends PluginConfigurationValueFloat
{
    public const int PRECISION = 1;

    protected ?DistanceUnitSystem $UnitSystem = null;

    public function __construct(string $Key, string $Label = '', string $Tooltip = '', mixed $DefaultValue = '')
    {
        parent::__construct($Key, $Label, $Tooltip, $DefaultValue);

        $this->DefaultValue *= $this->unitSystem()
            ->distanceToPreferredUnitFactor();
    }

    public function valueAsString(bool $transformValue = false): string
    {
        if ($transformValue) {
            return (string) round($this->value() * $this->unitSystem()->distanceToPreferredUnitFactor(), self::PRECISION);
        }

        return (string) round($this->value(), self::PRECISION);
    }

    public function setValueFromPost(): void
    {
        if (isset($_POST[$this->Key])) {
            $this->Value = (float) $_POST[$this->Key] * $this->unitSystem()->distanceToKmFactor();
        }
    }

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString(true));
        $Field->setSize(FormularInput::$SIZE_SMALL);

        if ($this->unitSystem()->isImperial()) {
            $Field->setUnit(FormularUnit::$MILES);
        } else {
            $Field->setUnit(FormularUnit::$KM);
        }

        return $Field;
    }

    protected function unitSystem(): DistanceUnitSystem
    {
        if (! $this->UnitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) {
            $this->UnitSystem = Configuration::General()->distanceUnitSystem();
        }

        return $this->UnitSystem;
    }
}
