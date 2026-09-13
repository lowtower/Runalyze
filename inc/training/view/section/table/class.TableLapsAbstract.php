<?php

declare(strict_types=1);

/**
 * This file contains class::TableLapsAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

/**
 * Table for laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TableLapsAbstract
{
    protected \Runalyze\Data\Laps\Laps $Laps;

    /**
     * Code
     */
    protected string $Code = '';

    public function __construct(
        protected \Runalyze\View\Activity\Context $Context
    ) {
        $this->setCode();
    }

    abstract protected function setCode();

    final public function getCode(): string
    {
        return $this->Code;
    }
}
