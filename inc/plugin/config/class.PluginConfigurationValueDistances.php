<?php

/**
 * This file contains class::PluginConfigurationValueDistances
 * @package Runalyze\Plugin
 */

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Plugin configuration value: array of distances [km|miles]
 *
 * Manages multiple distance values (e.g. for a "distances for yearly
 * comparison" setting), stored internally in km, displayed/edited in the
 * athlete's preferred unit system - analogous to the single-value
 * PluginConfigurationValueDistance, but for an array of values.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueDistances extends PluginConfigurationValueArray
{
    public const int PRECISION = 2;

    protected ?DistanceUnitSystem $UnitSystem = null;

    /**
     * Construct
     * @param array<int, float|int|string> $DefaultValue distances in km
     */
    public function __construct(string $Key, string $Label = '', string $Tooltip = '', array $DefaultValue = [])
    {
        parent::__construct($Key, $Label, $Tooltip, $DefaultValue);

        $this->DefaultValue = array_map(
            fn ($value): float => (float) $value * $this->unitSystem()
                ->distanceToPreferredUnitFactor(),
            $this->DefaultValue
        );
    }

    /**
     * Set value from a comma-separated string of distances, given in the
     * preferred unit system, converting each back to km for storage.
     */
    public function setValueFromString(string $Value): void
    {
        $factor = $this->unitSystem()
            ->distanceToKmFactor();

        $this->Value = array_map(
            fn (string $v): float => round((float) trim($v) * $factor, self::PRECISION),
            explode(',', $Value)
        );
    }

    /**
     * Get value as a comma-separated string, converted to the preferred
     * unit system for display.
     */
    public function valueAsString(): string
    {
        $factor = $this->unitSystem()
            ->distanceToPreferredUnitFactor();

        return implode(', ', array_map(
            fn (float $v): string => (string) round($v * $factor, self::PRECISION),
            $this->Value
        ));
    }

    public function setValueFromPost(): void
    {
        if (isset($_POST[$this->Key])) {
            $this->setValueFromString($_POST[$this->Key]);
        }
    }

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString());
        $Field->setSize(FormularInput::$SIZE_FULL_INLINE);
        $Field->addAttribute('maxlength', (string) PluginConfigurationValue::MAXLENGTH);

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
