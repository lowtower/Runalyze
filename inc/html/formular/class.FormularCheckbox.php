<?php

declare(strict_types=1);

/**
 * This file contains class::FormularCheckbox
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for a checkbox
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class FormularCheckbox extends FormularField
{
    /**
     * Boolean flag: label is on the right side
     */
    private bool $labelOnRight = false;

    /**
     * Boolean flag: add hidden field with '..._sent'
     */
    private bool $addHiddenSent = false;

    /**
     * Prepare for beeing displayed
     */
    protected function prepareForDisplay()
    {
        $this->addAttribute('type', 'checkbox');
        $this->addAttribute('name', $this->name);
        $this->setId($this->name);

        if ($this->value) {
            $this->addAttribute('checked', 'checked');
        }
    }

    /**
     * Set label to the right side
     */
    public function setLabelToRight(): void
    {
        $this->labelOnRight = true;
    }

    /**
     * Add hidden value for sent-flag
     */
    public function addHiddenSentValue(): void
    {
        $this->addHiddenSent = true;
    }

    /**
     * Display this field
     */
    protected function getFieldCode(): string
    {
        $label = '<label class="checkable" for="' . $this->name . '">' . $this->label . '</label>';
        $input = '<input ' . $this->attributes() . '>';

        if ($this->addHiddenSent) {
            $input .= HTML::hiddenInput($this->name . '_sent', 'true');
        }

        if ($this->labelOnRight) {
            return $input . ' ' . $label;
        }

        return $label . ' ' . $input;
    }
}
