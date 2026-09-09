<?php

/**
 * This file contains class::PluginConfigurationValueDistances
 * @package Runalyze\Plugin
 */

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Plugin configuration value: array
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueDistances extends PluginConfigurationValueArray
{
    /**
     * @var int
     */
    public const PRECISION = 2;

    protected ?DistanceUnitSystem $UnitSystem = null;

    /**
     * Construct
     * @param string $Label [optional]
     * @param string $Tooltip [optional]
     * @param string|array $DefaultValue [optional]
     */
    public function __construct(string $Key, string $Label = '', string $Tooltip = '', $DefaultValue = '')
    {
        parent::__construct($Key, $Label, $Tooltip, $DefaultValue);

        $this->DefaultValue = $this->roundAndTransformValues($this->DefaultValue, true, false);
    }

    /**
     * Has to be overwritten in subclasses.
     */
    public function setValueFromString(string $Value, bool $transformValues = false)
    {
        parent::setValueFromString($Value);

        $this->Value = $this->roundAndTransformValues($this->Value, $transformValues, true);
    }

    /**
     * Get value as string
     */
    public function valueAsString(bool $transformValues = false): string
    {
        $values = $this->roundAndTransformValues($this->Value, $transformValues, false);

        return implode(', ', $values);
    }

    protected function roundAndTransformValues(array $values, bool $transformValues = false, bool $toKm = false): array
    {
        if ($transformValues) {
            $factor = $toKm ? $this->unitSystem()
                ->distanceToKmFactor() : $this->unitSystem()
                ->distanceToPreferredUnitFactor();

            return array_map(function ($val) use ($factor): float {
                return round($val * $factor, self::PRECISION);
            }, $values);
        }

        return array_map(function ($val) {
            return round($val, self::PRECISION);
        }, $values);
    }

    public function setValueFromPost()
    {
        if (isset($_POST[$this->Key])) {
            $this->setValueFromString($_POST[$this->Key], true);
        }
    }

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString(true));
        $Field->setSize(FormularInput::$SIZE_FULL_INLINE);
        $Field->addAttribute('maxlength', PluginConfigurationValue::MAXLENGTH);

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
