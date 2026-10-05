<?php

/**
 * This file contains class::ConfigTabs
 * @package Runalyze\System\Config
 */
/**
 * @author Hannes Christiansen
 * @package Runalyze\System\Config
 */
class ConfigTabs
{
    /**
     * URL for config-window
     * @var string
     */
    public static $CONFIG_URL = 'settings';

    /**
     * HTML-ID for div
     * @var string
     */
    public static $TABS_ID = 'config-tabs';

    /**
     * Messages to show after submit
     * @var array
     */
    private static $Messages = [];

    /**
     * Internal array with all tabs
     * @var array
     */
    protected $Tabs = [];

    /**
     * Default key
     * @var string
     */
    protected $defaultKey = '';

    /**
     * Add message after submit
     * @param string $HTMLcode HTML::info() or HTML::text() or HTML::warning() or HTML::error()
     */
    public static function addMessage($HTMLcode): void
    {
        self::$Messages[] = $HTMLcode;
    }

    /**
     * Add a tab and set it as the default one
     */
    public function addDefaultTab(ConfigTab $Tab): void
    {
        $this->defaultKey = $Tab->getKey();
        $this->addTab($Tab);
    }

    public function addTab(ConfigTab $Tab): void
    {
        $this->Tabs[$Tab->getKey()] = $Tab;
    }

    /**
     * Display tabs
     */
    public function display(): void
    {
        if (Request::param('form') === 'true') {
            $this->Tabs[$this->getCurrentKey()]->parsePostData();

            $SubmitInfo = '<p class="okay"><em>' . __('The settings have been saved.') . '</em></p>';

            if (! empty(self::$Messages)) {
                $SubmitInfo .= implode('', self::$Messages);
            }

            echo '<div class="panel-heading" id="submit-info">' . $SubmitInfo . '</div>';
            echo Ajax::getReloadCommand();
        }

        if ($this->hasToShowDiv()) {
            echo '<div id="' . self::$TABS_ID . '">';
        }

        $this->displayCurrentTab();

        if ($this->hasToShowDiv()) {
            echo '</div>';
        }
    }

    /**
     * Has to show surrounding div and navigation?
     */
    private function hasToShowDiv(): bool
    {
        return Request::param('key') === '' || Request::param('form') === 'true' || Request::param('external') === 'true';
    }

    protected function displayCurrentTab()
    {
        $CurrentKey = $this->getCurrentKey();

        if (isset($this->Tabs[$CurrentKey])) {
            $this->Tabs[$CurrentKey]->display();
        }
    }

    /**
     * @return string
     */
    protected function getCurrentKey()
    {
        $CurrentKey = Request::param('key');

        if (empty($CurrentKey)) {
            return $this->defaultKey;
        }

        return $CurrentKey;
    }
}
