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
    /**
     * @var \Runalyze\View\Activity\Context
     */
    protected $Context;

    /**
     * @var \Runalyze\Data\Laps\Laps
     */
    protected $Laps;

    /**
     * Code
     * @var string
     */
    protected $Code = '';

    public function __construct(Context $context)
    {
        $this->Context = $context;

        $this->setCode();
    }

    abstract protected function setCode();

    /**
     * @return string
     */
    final public function getCode()
    {
        return $this->Code;
    }
}
