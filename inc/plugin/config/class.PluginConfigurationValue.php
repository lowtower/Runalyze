<?php

/**
 * This file contains class::PluginConfigurationValue
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration value
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfigurationValue
{
    /**
     * Max length
     * @var int
     */
    public const MAXLENGTH = 255;

    /**
     * Value
     * @var mixed
     */
    protected $Value;

    /**
     * Construct
     * @param mixed $DefaultValue
     */
    public function __construct(
        protected string $Key,
        protected string $Label = '',
        protected string $Tooltip = '',
        protected $DefaultValue = ''
    ) {
    }

    final public function key(): string
    {
        return $this->Key;
    }

    /**
     * @return mixed
     */
    final public function value()
    {
        return $this->Value;
    }

    final public function setLabel(string $Label): void
    {
        $this->Label = $Label;
    }

    /**
     * @param string $Tooltip
     */
    final public function setTooltip($Tooltip): void
    {
        $this->Tooltip = $Tooltip;
    }

    /**
     * Label for form field
     */
    final protected function formLabel(): string
    {
        if (! empty($this->Tooltip)) {
            return Ajax::tooltip($this->Label, $this->Tooltip, true);
        }

        return $this->Label;
    }

    /**
     * @param mixed $Value
     */
    final public function setValue($Value): void
    {
        $this->Value = $Value;
    }

    /**
     * @param mixed $Value
     */
    final public function setDefaultValue($Value): void
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

    /**
     * Get value as string
     */
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

    /**
     * Display row for config form
     */
    public function getFormField(): ?\FormularField
    {
        $Field = new FormularInput($this->Key, $this->formLabel(), $this->valueAsString());
        $Field->setSize(FormularInput::$SIZE_MIDDLE);

        return $Field;
    }
}
