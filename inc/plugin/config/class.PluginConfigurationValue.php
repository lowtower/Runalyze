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
    /**
     * @var int
     */
    public const MAXLENGTH = 255;

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

    final protected function formLabel(): string
    {
        if (! empty($this->Tooltip)) {
            return Ajax::tooltip($this->Label, $this->Tooltip, true);
        }

        return $this->Label;
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

    public function setValueFromPost(): void
    {
        if (isset($_POST[$this->Key])) {
            $this->setValueFromString($_POST[$this->Key]);
        }
    }

    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString());
        $Field->setSize(FormularInput::$SIZE_MIDDLE);

        return $Field;
    }
}
