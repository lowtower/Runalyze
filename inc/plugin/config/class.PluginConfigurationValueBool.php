<?php

declare(strict_types=1);

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
     */
    public function setValueFromString(string $Value): void
    {
        $this->Value = ($Value === '1');
    }

    /**
     * Get value as string
     */
    public function valueAsString(): string
    {
        return $this->Value ? '1' : '0';
    }

    public function setValueFromPost(): void
    {
        $this->setValue(isset($_POST[$this->Key]));
    }

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        return new FormularCheckbox($this->Key, $this->formLabel(), $this->valueAsString());
    }
}
