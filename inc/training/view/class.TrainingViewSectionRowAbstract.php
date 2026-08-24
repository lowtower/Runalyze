<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRowAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Row of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRowAbstract
{
    /**
     * @var \Runalyze\View\Activity\Context
     */
    protected $Context;

    public function __construct(?Context $Context = null)
    {
        $this->Context = $Context;

        $this->setContent();
    }

    abstract protected function setContent();

    abstract public function display();
}
