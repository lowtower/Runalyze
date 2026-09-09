<?php

/**
 * This file contains class::PluginConfigurationValueInt
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value: int
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueInt extends PluginConfigurationValue
{
    /**
     * Has to be overwritten in subclasses.
     */
    public function setValueFromString(string $Value)
    {
        $this->Value = (int) $Value;
    }

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString());
        $Field->setSize(FormularInput::$SIZE_SMALL);

        return $Field;
    }
}
