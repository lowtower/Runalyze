<?php

/**
 * This file contains class::ProgressBar
 * @package Runalyze\HTML
 */
/**
 * Progressbar (with HTML/CSS)
 *
 * @author Hannes Christiansen
 * @package Runalyze\HTML
 */
class ProgressBar
{
    /**
     * HTML class: surrounding div
     * @var string
     */
    private static $DIV_CONTAINER = 'progress-bar-container';

    /**
     * HTML class: container floating outer div
     * @var string
     */
    private static $CLASS_INLINE = 'progress-bar-container-inline';

    /**
     * HTML class: animated
     * @var string
     */
    private static $CLASS_ANIMATED = 'animated';

    /**
     * HTML class: container div
     * @var string
     */
    private static $DIV_INNER = 'progress-bar-inner';

    /**
     * HTML class: container div with icon
     * @var string
     */
    private static $DIV_BAR = 'progress-bar';

    /**
     * HTML class: div for goal line
     * @var string
     */
    private static $DIV_GOAL = 'progress-bar-goal';

    /**
     * Single progress bars
     * @var array
     */
    protected $Bars = [];

    /**
     * Tooltip
     * @var string
     */
    protected $Tooltip = '';

    /**
     * Additional classes
     * @var string
     */
    protected $AdditionalClasses = '';

    /**
     * Goal line
     * @var int
     */
    protected $Goal = 0;

    /**
     * Arguments can be passed to construct a single progress bar.
     * Otherwise, addBar($Width, $Color) has to be used.
     *
     * @param int $Width [optional] in percent between 0 and 100
     * @param string $Color [optional]
     */
    public function __construct($Width = 0, $Color = '')
    {
        if ($Width > 0) {
            $this->addNewBar($Width, $Color);
        }
    }

    /**
     * Add existing bar
     */
    public function addBar(ProgressBarSingle $SingleBar)
    {
        $this->Bars[] = $SingleBar;
    }

    /**
     * @param int $Width in percent between 0 and 100
     * @param string $Color
     */
    public function addNewBar($Width, $Color = '')
    {
        $this->Bars[] = new ProgressBarSingle($Width, $Color);
    }

    /**
     * @param string $Tooltip
     */
    public function setTooltip($Tooltip)
    {
        $this->Tooltip = $Tooltip;
    }

    /**
     * Set as inline progress bar
     */
    public function setInline()
    {
        $this->addClass(self::$CLASS_INLINE);
    }

    /**
     * Set as animated
     */
    public function setAnimated()
    {
        $this->addClass(self::$CLASS_ANIMATED);
    }

    /**
     * @param string $Class
     */
    public function addClass($Class)
    {
        $this->AdditionalClasses .= ' ' . $Class;
    }

    /**
     * @param int $Goal
     */
    public function setGoalLine($Goal)
    {
        $this->Goal = max(0, min(100, (int) $Goal));
    }

    public function display()
    {
        echo $this->getCode();
    }

    public function getCode(): string
    {
        $Code = '<div class="' . $this->getDivClass() . '"' . $this->getTooltip() . '>';
        $Code .= '<div class="' . self::$DIV_INNER . '">';
        $Code .= $this->getDivForSingleBars();
        $Code .= $this->getCodeForGoalLine();
        $Code .= '</div>';

        return $Code . '</div>';
    }

    /**
     * Get class
     */
    protected function getDivClass(): string
    {
        return self::$DIV_CONTAINER . $this->AdditionalClasses;
    }

    /**
     * Get additional stuff for tooltip
     */
    protected function getTooltip(): string
    {
        if (! empty($this->Tooltip)) {
            return ' ' . Ajax::tooltip('', $this->Tooltip, false, true);
        }

        return '';
    }

    /**
     * Get div for icon
     */
    protected function getDivForSingleBars(): string
    {
        $Code = '';

        foreach ($this->Bars as &$Bar) {
            $Code .= $this->getDivForSingleBar($Bar);
        }

        return $Code;
    }

    protected function getDivForSingleBar(ProgressBarSingle $SingleBar): string
    {
        $Code = '<div class="' . self::$DIV_BAR . ' ' . $SingleBar->getClasses() . '" style="width:' . $SingleBar->getWidth() . '%;">';

        return $Code . '</div>';
    }

    protected function getCodeForGoalLine(): string
    {
        if ($this->Goal > 0) {
            return '<div class="' . self::$DIV_GOAL . '" style="left:' . $this->Goal . '%;"></div>';
        }

        return '';
    }
}
