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
        $this->Value = array_map(trim(...), explode(',', $Value));
    }

    /**
     * Get value as string
     */
    public function valueAsString(): string
    {
        return implode(', ', $this->Value);
    }

    public function setValueFromPost(array $postData): void
    {
        if (isset($postData[$this->Key])) {
            $this->setValueFromString($postData[$this->Key]);
        }
    }
}
