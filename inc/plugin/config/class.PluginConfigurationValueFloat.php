<?php

declare(strict_types=1);

/**
 * This file contains class::PluginConfigurationValueInt
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value: int
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueFloat extends PluginConfigurationValue
{
    /**
     * Has to be overwritten in subclasses.
     */
    public function setValueFromString(string $Value): void
    {
        $this->Value = (float) $Value;
    }
}
