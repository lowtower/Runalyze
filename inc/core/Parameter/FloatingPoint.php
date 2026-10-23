<?php

declare(strict_types=1);

/**
 * This file contains class::FloatingPoint
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

use Helper;

/**
 * FloatingPoint (prev. Float)
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class FloatingPoint extends \Runalyze\Parameter
{
    /**
     * @var array<string, false>
     */
    #[\Override]
    protected array $Options = [
        'min' => false,
        'max' => false,
        'null' => false,
    ];

    public function setFromString(string $valueAsString): void
    {
        if ($valueAsString === '' && $this->Options['null']) {
            $this->set(null);
        } else {
            $this->set((float) Helper::CommaToPoint($valueAsString));
        }
    }

    public function set(mixed $value): void
    {
        if ($value === null && $this->Options['null']) {
            $value = null;
        } elseif ($this->Options['min'] !== false && $value < $this->Options['min']) {
            $value = $this->Options['min'];
        } elseif ($this->Options['max'] !== false && $value > $this->Options['max']) {
            $value = $this->Options['max'];
        }

        parent::set($value);
    }

    public function valueAsString(): string
    {
        $value = $this->value();

        if ($value === null && $this->Options['null']) {
            return '';
        }

        return (string) $value;
    }

    public function isEmpty(): bool
    {
        return $this->value() === null;
    }
}
