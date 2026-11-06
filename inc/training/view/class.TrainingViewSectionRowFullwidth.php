<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRowFullwidth
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Row of the training view (fullwidth)
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRowFullwidth extends TrainingViewSectionRowAbstract
{
    /**
     * Content
     * @var string
     */
    protected $Content = '';

    /**
     * CSS-id
     * @var string
     */
    protected $id = '';

    /**
     * Data for the twig template `activity/training_view/row.html.twig`
     *
     * @return array<string, mixed>
     */
    final public function templateData(): array
    {
        return [
            'kind' => 'fullwidth',
            'id' => $this->id,
            'content' => $this->Content,
        ];
    }
}
