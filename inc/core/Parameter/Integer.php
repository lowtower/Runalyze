<?php

declare(strict_types=1);

/**
 * This file contains class::Integer
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class Integer extends \Runalyze\Parameter
{
    public function setFromString(string $valueAsString): void
    {
        $this->set((int) $valueAsString);
    }
}
