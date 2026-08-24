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
class PluginConfigurationValueFloat extends PluginConfigurationValue
{
	/**
	 * Has to be overwritten in subclasses.
	 * @param string $Value
	 */
	public function setValueFromString($Value) {
		$this->Value = (float) $Value;
	}

    /**
     * Display row for config form
     */
    public function getFormField(): \FormularInput {
		$Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString());
		$Field->setSize( FormularInput::$SIZE_SMALL );

		return $Field;
	}
}
