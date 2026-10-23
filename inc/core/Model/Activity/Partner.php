<?php

/**
 * This file contains class::Partner
 * @package Runalyze\Model\Activity
 */

namespace Runalyze\Model\Activity;

use Runalyze\Model\StringArrayObject;

/**
 * Training partner
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity
 */
class Partner extends StringArrayObject
{
    /**
     * Separator
     */
    public const string SEPARATOR = ',';

    public function fromString(string $string): void
    {
        $this->Elements = array_map(trim(...), explode(self::SEPARATOR, $string));
    }

    /**
     * As string
     */
    public function asString(): string
    {
        return implode(self::SEPARATOR . ' ', $this->Elements);
    }
}
