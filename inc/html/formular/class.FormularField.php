<?php

/**
 * This file contains class::FormularField
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for a field as part of a formular
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
abstract class FormularField extends HtmlTag
{
    /**
     * CSS-class if validation failed
     */
    public static string $CSS_VALIDATION_FAILED = 'validation-failed';

    /**
     * Array with all failed keys
     */
    private static array $FAILED_KEYS = [];

    /**
     * Array with all validation failures
     */
    private static array $VALIDATION_FAILURES = [];

    /**
     * Value
     * @var string|array
     */
    protected $value = '';

    /**
     * Layout
     */
    protected string $layout = '';

    /**
     * CSS classes for layout
     * @var string[]
     */
    protected array $layoutClasses = [];

    /**
     * Enum from FormularValueParser
     * @var string enum
     */
    protected $parser;

    /**
     * Array with options for parser
     */
    protected array $parserOptions = [];

    /**
     * Field is prepared for being displayed
     */
    private bool $prepared = false;

    public static function setKeyAsFailed(string $key)
    {
        self::$FAILED_KEYS[] = $key;
    }

    /**
     * Set key as failed
     */
    public static function hasKeyFailed(string $key): bool
    {
        return in_array($key, self::$FAILED_KEYS);
    }

    public static function addValidationFailure(string $failure)
    {
        self::$VALIDATION_FAILURES[] = $failure;
    }

    /**
     * Get all validation failures
     */
    public static function getValidationFailures(): array
    {
        return self::$VALIDATION_FAILURES;
    }

    /**
     * Construct a new field
     * @param string|array $value, default: loading from $_POST
     */
    public function __construct(
        protected string $name,
        protected string $label,
        $value = ''
    ) {
        if (is_array($value)) {
            $this->value = $value;
        } elseif (strlen($value)) {
            $this->value = $value;
        } elseif (isset($_POST[$this->name])) {
            $this->value = $_POST[$this->name];
        } elseif (str_ends_with($this->name, ']')) {
            $posOfBracket = strpos($this->name, '[');
            $mainName = substr($this->name, 0, $posOfBracket);
            $key = substr($this->name, $posOfBracket + 1, -1);

            if (isset($_POST[$mainName][$key])) {
                $this->value = $_POST[$mainName][$key];
            }
        }
    }

    /**
     * Set default value if empty
     */
    public function defaultValue(string $value)
    {
        if (empty($this->value)) {
            $this->value = $value;
        }
    }

    /**
     * Set layout, used as css-class for a surrounding div
     */
    public function setLayout(string $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Set layout if no layout is set
     */
    public function setLayoutIfEmpty(string $layout)
    {
        if (empty($this->layout)) {
            $this->setLayout($layout);
        }
    }

    /**
     * Add layout, used as additional css-class for a surrounding div
     */
    public function addLayout(string $layout)
    {
        $this->layout .= ' ' . $layout;
    }

    /**
     * Add CSS class for layout
     */
    public function addLayoutClass(string $layout)
    {
        $this->layoutClasses[] = $layout;
    }

    public function setParser(string $parser, array $options = [])
    {
        $this->parser = $parser;
        $this->parserOptions = $options;
    }

    /**
     * Prepare for beeing display, may be overwritten
     */
    protected function prepareForDisplay()
    {
    }

    /**
     * Parse value for display
     */
    final protected function parseForDisplay()
    {
        FormularValueParser::parse($this->value, $this->parser, $this->parserOptions);

        if (self::hasKeyFailed($this->name)) {
            $this->addCSSclass(self::$CSS_VALIDATION_FAILED);
        }
    }

    /**
     * Validate value
     */
    public function validate(): bool
    {
        $validation = FormularValueParser::validatePost($this->name, $this->parser, $this->parserOptions);

        if (! $validation) {
            self::setKeyAsFailed($this->name);
            self::addValidationFailure(is_string($validation) ? $validation : __('Your input is not allowed.') . ' (' . $this->name . ')');
        }

        return $validation;
    }

    /**
     * Display this field
     */
    public function display()
    {
        echo $this->getCode();
    }

    /**
     * Get code for displaying this field with layout
     */
    final public function getCode(): string
    {
        $this->prepare();

        return $this->getSurroundedByLayoutDiv($this->getFieldCode());
    }

    /**
     * Surround given code by div container for layout
     */
    final protected function getSurroundedByLayoutDiv(string $Content): string
    {
        if (! empty($this->layout)) {
            return '<div class="' . $this->layout . ' ' . implode(' ', $this->layoutClasses) . '">' . $Content . '</div>';
        }

        return $Content;
    }

    /**
     * Prepare field for being displayed
     */
    private function prepare()
    {
        if (! $this->prepared) {
            $this->parseForDisplay();
            $this->prepareForDisplay();

            $this->prepared = true;
        }
    }

    /**
     * Get code for displaying the field
     */
    abstract protected function getFieldCode(): string;
}
