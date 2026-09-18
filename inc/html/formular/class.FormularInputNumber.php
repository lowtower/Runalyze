<?php

/**
 * This file contains class::FormularInputNumber
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for a standard number-input field
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class FormularInputNumber extends FormularInput
{
    /**
     * Minimum
     */
    protected ?int $min = null;

    /**
     * Maximum
     */
    protected ?int $max = null;

    /**
     * Prepare for beeing displayed
     */
    protected function prepareForDisplay()
    {
        $this->addAttribute('type', 'number');
        $this->addAttribute('name', $this->name);
        $this->addAttribute('value', $this->value);
        $this->setId($this->name);

        $this->addMinMax();
    }

    /**
     * Add min/max to tag
     */
    protected function addMinMax()
    {
        if ($this->min !== null) {
            $this->addAttribute('min', (string) $this->min);
        }

        if ($this->max !== null) {
            $this->addAttribute('max', (string) $this->max);
        }
    }

    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }
}
