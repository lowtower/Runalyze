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

    final protected function setPlot() {}

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

    /**
     * Plot part: tabbed right content
     *
     * @return array<string, mixed>
     */
    protected function plotTemplateData(): array
    {
        $tabs = [];

        foreach ($this->RightContent as $key => $Content) {
            $tabs[] = [
                'key' => (string) $key,
                'html' => $Content instanceof ActivityPlot
                    ? '<div id="plot-' . $Content->getKey() . '" class="plot-container">' . $this->plotHtml($Content) . '</div>'
                    : (string) $Content,
            ];
        }

        return [
            'kind' => 'tabbed_plot',
            'css_id' => $this->cssID,
            'top_content' => $this->TopContent,
            'tabs' => $tabs,
        ];
    }
}
