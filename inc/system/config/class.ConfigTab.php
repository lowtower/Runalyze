<?php

declare(strict_types=1);

/**
 * This file contains class::ConfigTab
 * @package Runalyze\System\Config
 */
/**
 * @author Hannes Christiansen
 * @package Runalyze\System\Config
 */
abstract class ConfigTab
{
    /**
     * HTML-Form
     * @var Formular
     */
    protected $Formular;

    /**
     * Title
     */
    protected string $title = '';

    /**
     * Key used as form-id
     */
    protected string $key = '';

    /**
     * Model factory
     */
    protected \Runalyze\Model\Factory $Model;

    /**
     * Set key and title for form
     */
    abstract protected function setKeyAndTitle();

    /**
     * Set all fieldsets and fields
     */
    abstract public function setFieldsetsAndFields();

    /**
     * Parse all post values
     */
    abstract public function parsePostData();

    /**
     * Construct new tab
     */
    public function __construct()
    {
        $this->Model = new Runalyze\Model\Factory(SessionAccountHandler::getId());

        $this->setKeyAndTitle();
    }

    final public function getKey(): string
    {
        return $this->key;
    }

    final public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Display formular
     */
    final public function display()
    {
        echo '<div class="panel-heading">';
        echo '<h1>' . $this->title . '</h1>';
        echo '</div>';
        echo '<div class="panel-content">';
        $this->displayFormular();
        echo '</div>';
    }

    private function displayFormular()
    {
        $this->Formular = new Formular($this->getUrl() . '&form=true');

        $this->setFieldsetsAndFields();

        $this->Formular->setId($this->key);
        $this->Formular->addCSSclass('ajax');
        $this->Formular->addCSSclass('no-automatic-reload');
        $this->Formular->addHiddenValue('configTabKey', $this->key);
        $this->Formular->addSubmitButton(__('Save'));
        $this->Formular->display();
    }

    /**
     * Get URL to this tab
     */
    final public function getUrl(): string
    {
        return 'settings?key=' . $this->key;
    }
}
