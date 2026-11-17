<?php

/**
 * This file contains class::PluginConfigurationValue
 * @package Runalyze\Plugin
 */

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValue
{
    public const int MAXLENGTH = 255;

    protected mixed $Value;

    public function __construct(
        protected string $Key,
        protected string $Label = '',
        protected string $Tooltip = '',
        protected mixed $DefaultValue = ''
    ) {
        $this->Value = $DefaultValue;
    }

    final public function key(): string
    {
        return $this->Key;
    }

    final public function label(): string
    {
        return $this->Label;
    }

    final public function tooltip(): string
    {
        return $this->Tooltip;
    }

    final public function value(): mixed
    {
        return $this->Value;
    }

    final public function setLabel(string $Label): void
    {
        $this->Label = $Label;
    }

    final public function setTooltip(string $Tooltip): void
    {
        $this->Tooltip = $Tooltip;
    }

    final public function setValue(mixed $Value): void
    {
        $this->Value = $Value;
    }

    final public function setDefaultValue(mixed $Value): void
    {
        $this->DefaultValue = $Value;
    }

    final public function setDefaultValueAsValue(): void
    {
        $this->Value = $this->DefaultValue;
    }

    /**
     * Has to be overwritten in subclasses.
     */
    public function setValueFromString(string $Value): void
    {
        $this->Value = $Value;
    }

    public function valueAsString(): string
    {
        return $this->value();
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function setValueFromPost(array $postData): void
    {
        if (isset($postData[$this->Key])) {
            $this->setValueFromString($postData[$this->Key]);
        }
    }
}
