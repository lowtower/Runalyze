<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSection
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Section of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSection
{
    /**
     * Header
     */
    protected string $Header = '';

    /**
     * All rows
     * @var TrainingViewSectionRowAbstract[]
     */
    protected array $Rows = [];

    /**
     * Is first?
     */
    protected bool $isFirst = false;

    protected \Runalyze\View\Activity\Context $Context;

    public function __construct(?Context $Context = null)
    {
        $this->Context = $Context;

        if ($this->hasRequiredData()) {
            $this->setHeaderAndRows();
        }
    }

    abstract protected function setHeaderAndRows();

    /**
     * Has the training all required data?
     */
    abstract protected function hasRequiredData(): bool;

    final protected function setHeader(string $header)
    {
        $this->Header = $header;
    }

    final protected function appendRow(TrainingViewSectionRowAbstract $Row)
    {
        $this->Rows[] = $Row;
    }

    final public function setAsFirst()
    {
        $this->isFirst = true;
    }

    /**
     * Is this section empty?
     */
    final public function isEmpty(): bool
    {
        return empty($this->Header) || $this->Rows === [];
    }

    public function display()
    {
        if (! $this->isEmpty()) {
            $this->displayHeader();
            $this->displayContent();
        }
    }

    private function displayHeader()
    {
        echo '<div class="panel-heading' . (! $this->isFirst ? ' panel-inner-heading' : '') . '">';
        echo '<h2>' . $this->Header . '</h2>';
        echo '</div>';
    }

    private function displayContent()
    {
        echo '<div>';

        foreach ($this->Rows as $Row) {
            $Row->display();
        }

        echo '</div>';
    }
}
