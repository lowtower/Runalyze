<?php

declare(strict_types=1);

/**
 * This file contains class::Parameter
 * @package Runalyze
 */

namespace Runalyze;

/**
 * @author Hannes Christiansen
 * @package Runalyze
 */
abstract class Parameter
{
    /**
     * Max length
     * @var int
     */
    public const MAX_LENGTH = 255;

    protected array $Options = [];

    private mixed $Value;

    public function __construct(mixed $value, array $options = [])
    {
        $this->Options = array_merge($this->Options, $options);
        $this->set($value);
    }

    public function set(mixed $value): void
    {
        $this->Value = $value;
    }

    public function setFromString(string $valueAsString): void
    {
        $this->set($valueAsString);
    }

    final public function value(): mixed
    {
        return $this->Value;
    }

    public function valueAsString(): string
    {
        return (string) $this->Value;
    }
}
