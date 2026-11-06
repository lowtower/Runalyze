<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRowAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

/**
 * Row of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRowAbstract
{
    public function __construct(
        protected ?\Runalyze\View\Activity\Context $Context = null
    ) {
        $this->setContent();
    }

    abstract protected function setContent();

    /**
     * Data for the twig template `activity/training_view/row.html.twig`
     *
     * @return array<string, mixed>
     */
    abstract public function templateData(): array;
}
