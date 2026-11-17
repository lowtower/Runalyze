<?php

declare(strict_types=1);

/**
 * This file contains class::PluginConfigurationValueInt
 * @package Runalyze\Plugin
 */

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueInt extends PluginConfigurationValue
{
    public function setValueFromString(string $Value): void
    {
        $this->Value = (int) $Value;
    }
}
