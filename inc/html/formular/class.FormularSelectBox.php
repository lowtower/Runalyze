<?php

/**
 * This file contains class::FormularSelectBox
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for a standard select box
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class FormularSelectBox extends FormularField
{
    /**
     * Array with all possible options
     * @var array
     */
    private $options = [];

    /**
     * Multiple
     * @var bool
     */
    protected $multiple = false;

    /**
     * Prepare for beeing displayed
     */
    protected function prepareForDisplay()
    {
        // No attributes needed, HTML-function used
    }

    public function setMultiple(): void
    {
        $this->multiple = true;
    }

    /**
     * Add option to selectBox
     * @param mixed $key
     * @param string $text
     * @param array $attributes
     */
    public function addOption($key, $text, $attributes = []): void
    {
        if (! empty($attributes)) {
            $attributes['text'] = $text;
            $text = $attributes;
        }

        $this->options[$key] = $text;
    }

    /**
     * Set all options
     * @param array $options keys are values for selectBox
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }

    /**
     * Display this field
     */
    protected function getFieldCode(): string
    {
        if ($this->multiple) {
            $this->addAttribute('multiple', 'multiple');
        }

        $label = '<label for="' . $this->name . '">' . $this->label . '</label>';
        $select = HTML::selectBox($this->name . ($this->multiple ? '[]' : ''), $this->options, $this->value, $this->name . '" ' . $this->attributes());

        return $label . $select;
    }
}
