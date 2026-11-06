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

    /**
     * @param string|ActivityPlot|array<string, mixed> $content html string, plot or table template data
     */
    final protected function addRightContent(string $key, string $title, string|ActivityPlot|array $content)
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
            if ($Content instanceof ActivityPlot) {
                $html = '<div id="plot-' . $Content->getKey() . '" class="plot-container">' . $this->plotHtml($Content) . '</div>';
                $table = null;
            } elseif (is_array($Content)) {
                $html = '';
                $table = $Content;
            } else {
                $html = (string) $Content;
                $table = null;
            }

            $tabs[] = [
                'key' => (string) $key,
                'html' => $html,
                'table' => $table,
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
