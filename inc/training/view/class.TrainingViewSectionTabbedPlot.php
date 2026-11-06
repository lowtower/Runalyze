<?php

/**
 * This file contains class::TrainingViewSectionTabbedPlot
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Section of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionTabbedPlot extends TrainingViewSection
{
    /**
     * All menus
     */
    protected array $Links = [];

    /**
     * Append row
     */
    final protected function appendRowTabbedPlot(TrainingViewSectionRowTabbedPlot $Row)
    {
        $this->Rows[] = $Row;
        $this->Links += $Row->getLinks();

        $Row->setCSSid($this->cssId());
    }


    /**
     * Data for the twig template `activity/training_view/section.html.twig`
     *
     * @return array<string, mixed> empty array if the section must not be displayed
     */
    public function templateData(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        return [
            'is_first' => $this->isFirst,
            'header' => $this->Header,
            'change_menu' => $this->changeMenuLinks(),
            'container_id' => '',
            'rows' => $this->rowsTemplateData($this->Rows),
        ];
    }

    /**
     * @return string[]
     */
    private function changeMenuLinks(): array
    {
        if (count($this->Links) <= 1) {
            return [];
        }

        $links = [];

        foreach ($this->Links as $key => $Title) {
            $links[] = Ajax::change($Title, 'training-view-tabbed-' . $this->cssId(), 'training-view-tabbed-' . $this->cssId() . '-' . $key);
        }

        $links[] = Ajax::change('<i class="fa fa-fw fa-bars"></i> ' . __('All'), 'training-view-tabbed-' . $this->cssId(), 'training-view-tabbed-' . $this->cssId());

        return $links;
    }

    /**
     * CSS-ID
     */
    abstract protected function cssId(): string;
}
