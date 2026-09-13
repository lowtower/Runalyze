<?php

/**
 * This file contains class::PluginConfigurationValueArray
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value: array
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueArray extends PluginConfigurationValue
{
    /**
     * Has to be overwritten in subclasses.
     */
    public function setValueFromString(string $Value): void
    {
        $this->Value = array_map('trim', explode(',', $Value));
    }

    /**
     * Get value as string
     */
    public function valueAsString(): string
    {
        return implode(', ', $this->Value);
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
        $Field->addAttribute('maxlength', PluginConfigurationValue::MAXLENGTH);

        return $Field;
    }
}
