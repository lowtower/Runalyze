<?php

declare(strict_types=1);

/**
 * This file contains class::PluginPanel
 * @package Runalyze\Plugin
 */
/**
 * Abstract plugin class for panels
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
abstract class PluginPanel extends Plugin
{
    /**
     * Internal flag: Show surrounding div
     */
    public bool $SurroundingDivIsVisible = true;

    /**
     * Boolean flag: Don't reload if config has changed
     */
    protected bool $dontReloadForConfig = false;

    /**
     * Boolean flag: Don't reload if a training has changed
     */
    protected bool $dontReloadForTraining = false;

    /**
     * Boolean flag: Remove padding from panel-content
     */
    protected bool $removePanelContentPadding = false;

    final public function type(): int
    {
        return PluginType::PANEL;
    }

    /**
     * Method for getting the right symbol(s) (implemented in each plugin)
     */
    protected function getRightSymbol(): string
    {
        return '';
    }

    /**
     * Includes the plugin-file for displaying the statistics
     */
    public function display()
    {
        $this->prepareForDisplay();

        if ($this->SurroundingDivIsVisible) {
            $classes = '';
            if ($this->dontReloadForConfig) {
                $classes .= ' ' . Plugin::$DONT_RELOAD_FOR_CONFIG_FLAG;
            }

            if ($this->dontReloadForTraining) {
                $classes .= ' ' . Plugin::$DONT_RELOAD_FOR_TRAINING_FLAG;
            }

            echo '<div class="panel' . $classes . '" id="panel-' . $this->id() . '">';
        }

        $this->displayHeader();

        echo '<div class="panel-content' . ($this->removePanelContentPadding ? ' nopadding' : '') . '"' . ($this->isHidden() ? ' style="display:none;"' : '') . '>';
        $this->displayContent();
        echo '</div>';

        if ($this->SurroundingDivIsVisible) {
            echo '</div>';
        }
    }

    /**
     * Displays the config container for this panel
     */
    public function getConfigLinks()
    {
        $Links = [];

        $Links[] = $this->getConfigLink();
        $Links[] = '<span class="link up" rel="' . $this->id() . '">' . Icon::$UP . '</span>';
        $Links[] = '<span class="link down" rel="' . $this->id() . '">' . Icon::$DOWN . '</span>';
        $Links[] = $this->getReloadLink();

        return implode('', $Links);
    }

    private function displayHeader()
    {
        echo '<div class="panel-heading">';
        echo '<div class="panel-menu">' . $this->getRightSymbol() . '</div>';
        echo '<h1 class="link clap" rel="' . $this->id() . '">' . $this->name() . '</h1>';
        echo '<div class="hover-icons">' . $this->getConfigLinks() . '</div>';
        echo '</div>';
    }

    /**
     * Set flag for visibility of the surrounding div
     */
    public function setSurroundingDivVisible(bool $value = true)
    {
        $this->SurroundingDivIsVisible = $value;
    }

    /**
     * Returns the html-link to this plugin for tab-navigation
     */
    public function getLink(): string
    {
        throw new BadMethodCallException('PluginPanel does not support getLink().');
    }

    /**
     * Returns the html-link for inner-html-navigation
     * @param string $name displayed link-name
     * @param int $sport id of sport, default $this->sportid
     * @param int $year year, default $this->year
     * @param string $dat optional dat-parameter
     */
    protected function getInnerLink(string $name, int $sport = 0, int $year = 0, string $dat = ''): string
    {
        throw new BadMethodCallException('PluginPanel does not support getInnerLink().');
    }
}
