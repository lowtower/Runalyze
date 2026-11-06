<?php

/**
 * This file contains class::TrainingViewSectionTabbed
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Section of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionTabbed extends TrainingViewSection
{
    /**
     * All tabbed rows
     * @var TrainingViewSectionRowAbstract[]
     */
    protected array $RowsTabbed = [];

    /**
     * All tabbed rows: Title
     * @var string[]
     */
    protected array $RowsTabbedTitle = [];

    /**
     * Append row
     */
    final protected function appendRowTabbed(TrainingViewSectionRowAbstract $Row, string $Title)
    {
        $this->RowsTabbed[] = $Row;
        $this->RowsTabbedTitle[] = $Title;
    }

    /**
     * Data for the twig template `activity/training_view/section.html.twig`
     *
     * @return array<string, mixed> empty array if the section must not be displayed
     */
    public function templateData(): array
    {
        if (empty($this->Header) || ($this->RowsTabbed === [] && $this->Rows === [])) {
            return [];
        }

        return [
            'is_first' => $this->isFirst,
            'header' => $this->Header,
            'change_menu' => $this->changeMenuLinks(),
            'container_id' => 'training-view-tabbed-' . $this->cssId(),
            'rows' => $this->tabbedRowsTemplateData(),
        ];
    }

    /**
     * @return string[]
     */
    private function changeMenuLinks(): array
    {
        if (count($this->RowsTabbedTitle) <= 1) {
            return [];
        }

        $links = [];

        foreach ($this->RowsTabbedTitle as $i => $Title) {
            $links[] = Ajax::change($Title, 'training-view-tabbed-' . $this->cssId(), 'training-view-tabbed-' . $this->cssId() . '-' . $i);
        }

        return $links;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tabbedRowsTemplateData(): array
    {
        $data = [];

        foreach ($this->RowsTabbed as $i => $Row) {
            $data[] = [
                'tab_id' => 'training-view-tabbed-' . $this->cssId() . '-' . $i,
                'hidden' => $i != 0,
                'data' => $Row->templateData(),
            ];
        }

        return $data;
    }

    /**
     * CSS-ID
     */
    abstract protected function cssId(): string;
}
