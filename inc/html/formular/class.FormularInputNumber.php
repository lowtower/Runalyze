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
     * @var int
     */
    protected $min;

    /**
     * Maximum
     * @var int
     */
    protected $max;

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
            $this->addAttribute('min', (int) $this->min);
        }

        if ($this->max !== null) {
            $this->addAttribute('max', (int) $this->max);
        }
    }

    /**
     * @param int $min
     */
    public function setMin($min): void
    {
        $this->min = $min;
    }

    /**
     * @param int $max
     */
    public function setMax($max): void
    {
        $this->max = $max;
    }
}
