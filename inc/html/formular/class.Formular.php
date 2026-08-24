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
     * @var array
     */
    protected $hiddenValues = [];

    /**
     * Array with all fieldsets
     * @var FormularFieldset[]
     */
    protected $fieldsets = [];

    /**
     * Array with all submit buttons, keys are names
     * @var array
     */
    protected $submitButtons = [];

    /**
     * Boolean flag: submit buttons centered
     * @var bool
     */
    protected $submitButtonsCentered = false;

    /**
     * Formular action
     * @var string
     */
    protected $action = '';

    /**
     * Formular method
     * @var string
     */
    protected $method = '';

    /**
     * H1-header
     * @var string
     */
    protected $header = '';

    /**
     * All failures, beeing displayed above submit-button
     * @var array
     */
    protected $failures = [];

    /**
     * Construct a new formular
     * @param string $action
     * @param string $method
     */
    public function __construct($action = '', $method = 'post')
    {
        if (empty($action)) {
            $action = $_SERVER['REQUEST_URI'];
        }

        $this->action = $action;
        $this->method = $method;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @param string $string
     */
    public function setHeader($string)
    {
        $this->header = $string;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addHiddenValue($name, $value = '')
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
    public function addFieldset(FormularFieldset $Fieldset, $opened = true)
    {
        if ($Fieldset->getId() == '') {
            $Fieldset->setId($this->Id . '_legend_' . count($this->fieldsets));
        }

        if (! $opened) {
            $Fieldset->setCollapsed();
        }

        $this->fieldsets[] = $Fieldset;
    }

    final public function validateAllFieldsets()
    {
        foreach ($this->fieldsets as &$Fieldset) {
            $Fieldset->validateAllFields();
        }
    }

    /**
     * @param string $value
     * @param string $name optional, default 'submit'
     */
    public function addSubmitButton($value, $name = 'submit')
    {
        $this->submitButtons[$name] = $value;
    }

    public function setSubmitButtonsCentered()
    {
        $this->submitButtonsCentered = true;
    }

    /**
     * Add a failure, displayed above submit-button
     * @param string $message
     */
    public function addFailure($message)
    {
        $this->failures[] = $message;
    }

    /**
     * Set a specific layout for all fields in every fieldset
     */
    public function setLayoutForFields($layout)
    {
        foreach ($this->fieldsets as &$Fieldset) {
            $Fieldset->setLayoutForFields($layout);
        }
    }

    /**
     * Set toggle-function: only one opened fieldset
     */
    public function allowOnlyOneOpenedFieldset()
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
    public function display()
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
