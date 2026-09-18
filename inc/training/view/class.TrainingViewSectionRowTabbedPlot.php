<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRowTabbedPlot
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Plot\ActivityPlot;

/**
 * Row of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRowTabbedPlot extends TrainingViewSectionRow
{
    /**
     * Right content
     */
    protected array $RightContent = [];

    /**
     * Right content title
     */
    protected array $RightContentTitle = [];

    protected string $TopContent = '';

    /**
     * CSS id
     */
    protected string $cssID = '';

    public function __construct(?Context $Context = null)
    {
        $this->Context = $Context;

        $this->setContent();
        $this->setRightContent();
    }

    final public function setCSSid(string $cssID)
    {
        $this->cssID = $cssID;
    }

    final protected function setPlot()
    {
    }

    abstract protected function setRightContent();

    final protected function addRightContent(string $key, string $title, string|ActivityPlot $content)
    {
        $this->RightContent[$key] = $content;
        $this->RightContentTitle[$key] = $title;
    }

    final public function getLinks(): array
    {
        return $this->RightContentTitle;
    }

    protected function displayPlot()
    {
        echo '<div id="training-view-tabbed-' . $this->cssID . '" class="training-row-plot">';

        $first = true;

        echo $this->TopContent;

        foreach ($this->RightContent as $key => $Content) {
            echo '<div class="change" id="training-view-tabbed-' . $this->cssID . '-' . $key . '"' . (! $first ? ' style="display:none;"' : '') . '>';

            if ($Content instanceof ActivityPlot) {
                echo '<div id="plot-' . $Content->getKey() . '" class="plot-container">';
                $Content->display();
                echo '</div>';
            } else {
                echo $Content;
            }

            echo '</div>';

            $first = false;
        }

        echo '</div>';
    }
}
