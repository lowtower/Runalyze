<?php

declare(strict_types=1);

/**
 * This file contains class::TableLapsAbstract
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Table for laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TableLapsAbstract
{
    protected \Runalyze\View\Activity\Context $Context;

    protected \Runalyze\Data\Laps\Laps $Laps;

    /**
     * Code
     */
    protected string $Code = '';

    public function __construct(Context $context)
    {
        $this->Context = $context;

        $this->setCode();
    }

    abstract protected function setCode();

    final public function getCode(): string
    {
        return $this->Code;
    }
}
