<?php

/**
 * This file contains class::Formular
 * @package Runalyze\HTML\Formular
 */
/**
 * Class for displaying a formular
 *
 * @author Hannes Christiansen
 * @package Runalyze\HTML\Formular
 */
class Formular extends HtmlTag
{
    /**
     * Array with all hidden values, keys are names
     */
    protected array $hiddenValues = [];

    /**
     * Array with all fieldsets
     * @var FormularFieldset[]
     */
    protected array $fieldsets = [];

    /**
     * Array with all submit buttons, keys are names
     */
    protected array $submitButtons = [];

    /**
     * Boolean flag: submit buttons centered
     */
    protected bool $submitButtonsCentered = false;

    /**
     * Formular action
     */
    protected string $action = '';

    /**
     * H1-header
     */
    protected string $header = '';

    /**
     * All failures, beeing displayed above submit-button
     * @var string[]
     */
    protected array $failures = [];

    /**
     * Construct a new formular
     */
    public function __construct(
        string $action = '',
        protected string $method = 'post'
    ) {
        if (empty($action)) {
            $action = $_SERVER['REQUEST_URI'];
        }

        $this->action = $action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function setHeader(string $string): void
    {
        $this->header = $string;
    }

    public function addHiddenValue(string $name, string $value = ''): void
    {
        if (empty($value) && isset($_POST[$name])) {
            $value = $_POST[$name];
        }

        $this->hiddenValues[$name] = $value;
    }

    /**
     * Add a fieldset to formular
     * @param bool $opened [optional]
     */
    public function addFieldset(FormularFieldset $Fieldset, bool $opened = true): void
    {
        if ($Fieldset->getId() == '') {
            $Fieldset->setId($this->Id . '_legend_' . count($this->fieldsets));
        }

        if (! $opened) {
            $Fieldset->setCollapsed();
        }

        $this->fieldsets[] = $Fieldset;
    }

    final public function validateAllFieldsets(): void
    {
        foreach ($this->fieldsets as &$Fieldset) {
            $Fieldset->validateAllFields();
        }
    }

    /**
     * @param string $name optional, default 'submit'
     */
    public function addSubmitButton(string $value, string $name = 'submit'): void
    {
        $this->submitButtons[$name] = $value;
    }

    public function setSubmitButtonsCentered(): void
    {
        $this->submitButtonsCentered = true;
    }

    /**
     * Add a failure, displayed above submit-button
     */
    public function addFailure(string $message): void
    {
        $this->failures[] = $message;
    }

    /**
     * Set a specific layout for all fields in every fieldset
     */
    public function setLayoutForFields($layout): void
    {
        foreach ($this->fieldsets as &$Fieldset) {
            $Fieldset->setLayoutForFields($layout);
        }
    }

    /**
     * Set toggle-function: only one opened fieldset
     */
    public function allowOnlyOneOpenedFieldset(): void
    {
        foreach ($this->fieldsets as &$Fieldset) {
            $Fieldset->allowOnlyOneOpenedFieldset();
        }
    }

    /**
     * Prepare object for beeing displayed
     */
    protected function prepareForDisplay()
    {
        $this->addAttribute('action', $this->action);
        $this->addAttribute('method', $this->method);

        $this->prepareForDisplayInSublcass();
    }

    /**
     * Additional preparation for subclasses
     */
    protected function prepareForDisplayInSublcass()
    {
    }

    /**
     * Display this formular
     */
    public function display(): void
    {
        $this->prepareForDisplay();

        echo '<div class="panel-content">';
        echo '<form ' . $this->attributes() . '>';

        $this->displayHeader();
        $this->displayHiddenValues();
        $this->displayFieldsets();
        $this->displayFailures();
        $this->displaySubmitButtons();

        echo '</form>';
        echo '</div>';
    }

    protected function displayHeader()
    {
        if (! empty($this->header)) {
            echo '<h1>' . $this->header . '</h1>';
        }
    }

    protected function displayHiddenValues()
    {
        foreach ($this->hiddenValues as $name => $value) {
            echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
        }
    }

    protected function displayFieldsets()
    {
        foreach ($this->fieldsets as $Fieldset) {
            $Fieldset->display();
        }
    }

    protected function displayFailures()
    {
        foreach ($this->failures as $message) {
            echo HTML::error($message);
        }
    }

    protected function displaySubmitButtons()
    {
        if ($this->submitButtonsCentered) {
            echo '<div class="c">';
        }

        foreach ($this->submitButtons as $name => $value) {
            echo '<input type="submit" name="' . $name . '" value="' . $value . '">';
        }

        if ($this->submitButtonsCentered) {
            echo '</div>';
        }
    }
}
