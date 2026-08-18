<?php

declare(strict_types=1);

namespace Runalyze\Sports\ClimbQuantification;

interface CategorizableInterface
{
    /**
     * @return array
     */
    public function getLowerLimitsForCategorization();
}
