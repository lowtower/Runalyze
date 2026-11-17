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

    public function setValueFromPost(array $postData): void
    {
        if (isset($postData[$this->Key])) {
            $this->Value = (float) $postData[$this->Key] * $this->unitSystem()->distanceToKmFactor();
        }
    }

    public function usesImperialUnits(): bool
    {
        return $this->unitSystem()->isImperial();
    }

    protected function unitSystem(): DistanceUnitSystem
    {
        if (! $this->UnitSystem instanceof DistanceUnitSystem) {
            $this->UnitSystem = Configuration::General()->distanceUnitSystem();
        }

        return $this->UnitSystem;
    }
}
