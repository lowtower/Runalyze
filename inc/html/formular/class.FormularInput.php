<?php

/**
 * This file contains class::FormularInput
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for a standard input field
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class FormularInput extends FormularField
{
    /**
     * Size: full inline
     * @var string
     */
    public static $SIZE_FULL_INLINE = 'full-size';

    /**
     * Size: full
     * @var string
     */
    public static $SIZE_FULL = 'fullwidth';

    /**
     * Size: middle
     * @var string
     */
    public static $SIZE_MIDDLE = 'middle-size';

    /**
     * Size: small
     * @var string
     */
    public static $SIZE_SMALL = 'small-size';

    /**
     * Standard size for fields
     * @var string
     */
    private static $standardSize = 'small-size';

    /**
     * Size
     */
    protected string $size = '';

    /**
     * Unit
     */
    protected string $unit = '';

    /**
     * Boolean flag: label is on the right side
     */
    private bool $labelOnRight = false;

    /**
     * Boolean flag: hide label
     */
    private bool $hideLabel = false;

    /**
     * Set standard size, used when no specific size is set (may be overwritten by css)
     */
    public static function setStandardSize(string $size): void
    {
        self::$standardSize = $size;
    }

    /**
     * Set label to the right side
     */
    public function setLabelToRight(): void
    {
        $this->labelOnRight = true;
    }

    public function hideLabel(): void
    {
        $this->hideLabel = true;
    }

    /**
     * Size size for this input field
     */
    public function setSize(string $size): void
    {
        $this->size = $size;
    }

    /**
     * Set specific unit for this input field
     */
    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * Set input field disabled
     */
    public function setDisabled(): void
    {
        $this->addAttribute('disabled', 'disabled');
    }

    /**
     * Prepare for beeing displayed
     */
    protected function prepareForDisplay()
    {
        $this->addAttribute('type', 'text');
        $this->addAttribute('name', $this->name);
        $this->addAttribute('value', $this->value);
        $this->setId($this->name);

        $this->addUnitAndSize();
    }

    /**
     * Add unit and size as css-classes
     */
    protected function addUnitAndSize()
    {
        if (! empty($this->size)) {
            $this->addCSSclass($this->size);
        } elseif (! empty(self::$standardSize)) {
            $this->addCSSclass(self::$standardSize);
        }
    }

    public function setPlaceholder(string $text): void
    {
        $this->addAttribute('placeholder', $text);
    }

    /**
     * Display this field
     */
    protected function getFieldCode(): string
    {
        $label = '<label for="' . $this->name . '">' . $this->label . '</label>';
        $input = $this->wrapInputTagForUnit('<input ' . $this->attributes() . '>');

        if ($this->hideLabel) {
            $label = '';
        }

        if ($this->labelOnRight) {
            return $input . ' ' . $label;
        }

        return $label . ' ' . $input;
    }

    /**
     * Wrap input for unit
     */
    final protected function wrapInputTagForUnit(string $inputTag): string
    {
        if (empty($this->unit)) {
            return $inputTag;
        }

        return '<span class="input-with-unit">' . $inputTag . '<label for="' . $this->name . '" class="input-unit">' . $this->unit . '</label></span>';
    }
}
