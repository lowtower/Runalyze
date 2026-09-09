<?php

declare(strict_types=1);

/**
 * This file contains class::StringObject
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Abstract string object
 *
 * A string object represents a complete object that can be created by strings
 * and be saved as string.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class StringObject
{
    /**
     * Construct
     * @param string|array|null $data string or array in inheritants
     */
    public function __construct($data = '')
    {
        $this->fromString((string) $data);
    }

    abstract public function fromString(string $string);

    abstract public function asString(): string;

    public function isEmpty(): bool
    {
        return $this->asString() === '';
    }
}
