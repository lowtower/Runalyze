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
     */
    private static string $DIV_CONTAINER = 'progress-bar-container';

    /**
     * HTML class: container floating outer div
     */
    private static string $CLASS_INLINE = 'progress-bar-container-inline';

    /**
     * HTML class: animated
     */
    private static string $CLASS_ANIMATED = 'animated';

    /**
     * HTML class: container div
     */
    private static string $DIV_INNER = 'progress-bar-inner';

    /**
     * HTML class: container div with icon
     */
    private static string $DIV_BAR = 'progress-bar';

    /**
     * HTML class: div for goal line
     */
    private static string $DIV_GOAL = 'progress-bar-goal';

    /**
     * Single progress bars
     * @var \ProgressBarSingle[]
     */
    protected array $Bars = [];

    /**
     * Tooltip
     */
    protected string $Tooltip = '';

    /**
     * Additional classes
     */
    protected string $AdditionalClasses = '';

    /**
     * Goal line
     */
    protected int $Goal = 0;

    /**
     * Arguments can be passed to construct a single progress bar.
     * Otherwise, addBar($Width, $Color) has to be used.
     *
     * @param int $Width [optional] in percent between 0 and 100
     * @param string $Color [optional]
     */
    public function __construct(int $Width = 0, string $Color = '')
    {
        if ($Width > 0) {
            $this->addNewBar($Width, $Color);
        }
    }

    /**
     * Add existing bar
     */
    public function addBar(ProgressBarSingle $SingleBar): void
    {
        $this->Bars[] = $SingleBar;
    }

    /**
     * @param int $Width in percent between 0 and 100
     */
    public function addNewBar(int $Width, string $Color = ''): void
    {
        $this->Bars[] = new ProgressBarSingle($Width, $Color);
    }

    public function setTooltip(string $Tooltip): void
    {
        $this->Tooltip = $Tooltip;
    }

    /**
     * Set as inline progress bar
     */
    public function setInline(): void
    {
        $this->addClass(self::$CLASS_INLINE);
    }

    /**
     * Set as animated
     */
    public function setAnimated(): void
    {
        $this->addClass(self::$CLASS_ANIMATED);
    }

    public function addClass(string $Class): void
    {
        $this->AdditionalClasses .= ' ' . $Class;
    }

    public function setGoalLine(int $Goal): void
    {
        $this->Goal = max(0, min(100, $Goal));
    }

    public function display(): void
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
