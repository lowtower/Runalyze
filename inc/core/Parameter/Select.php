<?php

/**
 * This file contains class::Select
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class Select extends \Runalyze\Parameter
{
    protected bool $UseFallback = true;

    public function __construct(
        protected string $Default,
        array $options = []
    ) {
        $options = array_merge([
            'options' => [],
        ], $options);

        parent::__construct($this->Default, $options);
    }

    /**
     * Set value
     * @param mixed $value new value
     * @throws \InvalidArgumentException
     */
    public function set(mixed $value): void
    {
        if ($this->valueIsAllowed($value)) {
            parent::set($value);
        } elseif ($this->UseFallback) {
            parent::set($this->Default);
        } else {
            throw new \InvalidArgumentException('Invalid option ("' . $value . '") for select value.');
        }
    }

    /**
     * Value allowed?
     */
    protected function valueIsAllowed(string $value): bool
    {
        return in_array($value, array_keys($this->Options['options']));
    }

    /**
     * Long string
     */
    public function valueAsLongString(): string
    {
        return $this->Options['options'][$this->value()];
    }

    public function options(): array
    {
        return $this->Options['options'];
    }
}
