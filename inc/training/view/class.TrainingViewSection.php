<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSection
 * @package Runalyze\DataObjects\Training\View\Section
 */

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

    public function __construct(
        protected ?\Runalyze\View\Activity\Context $Context = null
    ) {
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
            'change_menu' => [],
            'container_id' => '',
            'rows' => $this->rowsTemplateData($this->Rows),
        ];
    }

    /**
     * @param TrainingViewSectionRowAbstract[] $rows
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rowsTemplateData(array $rows): array
    {
        $data = [];

        foreach ($rows as $Row) {
            $data[] = [
                'tab_id' => '',
                'hidden' => false,
                'data' => $Row->templateData(),
            ];
        }

        return $data;
    }
}
