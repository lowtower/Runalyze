<?php

declare(strict_types=1);

/**
 * This file contains class::PluginConfigurationValueSelect
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value: select
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValueSelect extends PluginConfigurationValue
{
    protected array $Options = [];

    public function setOptions(array $Options): void
    {
        $this->Options = $Options;
    }

    public function options(): array
    {
        return $this->Options;
    }
}
