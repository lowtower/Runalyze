<?php

/**
 * This file contains class::PluginConfigurationValueBool
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value: bool
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueBool extends PluginConfigurationValue
{
    /**
     * Has to be overwritten in subclasses.
     * @param string $Value
     */
    public function setValueFromString($Value)
    {
        $this->Value = ($Value == '1');
    }

    /**
     * Get value as string
     */
    public function valueAsString(): string
    {
        return $this->Value ? '1' : '0';
    }

    public function setValueFromPost()
    {
        $this->setValue(isset($_POST[$this->Key]));
    }

    /**
     * Display row for config form
     */
    public function getFormField(): \FormularField
    {
        return new FormularCheckbox($this->Key, $this->formLabel(), $this->valueAsString());
    }
}
