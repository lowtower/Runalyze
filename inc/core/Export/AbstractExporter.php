<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractExporter
 * @package Runalyze\Export
 */

namespace Runalyze\Export;

use Runalyze\View\Activity\Context;

/**
 * Create exporter for given type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export
 */
abstract class AbstractExporter
{
    public function __construct(
        protected ?Context $Context
    ) {
    }

    abstract public function iconClass(): string;

    abstract public function isPossible(): bool;
}
