<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRowOnlyText
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Row of the training view: only text
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRowOnlyText extends TrainingViewSectionRowAbstract
{
    /**
     * Boxed values
     * @var BoxedValue[]
     */
    protected $BoxedValues = [];

    /**
     * Content: right
     * @var string
     */
    protected $ContentRight = '';

    /**
     * Content: left
     * @var string
     */
    protected $ContentLeft = '';


    /**
     * Data for the twig template `activity/training_view/row.html.twig`
     *
     * @return array<string, mixed>
     */
    final public function templateData(): array
    {
        return [
            'kind' => 'only_text',
            'content_left' => $this->ContentLeft,
            'content_right' => $this->ContentRight,
        ];
    }
}
