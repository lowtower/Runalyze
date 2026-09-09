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

    /**
     * @var mixed
     */
    protected $Default;

    /**
     * Construct
     * @param string $default
     * @param array $options [optional]
     */
    public function __construct($default, array $options = [])
    {
        $this->Default = $default;
        $options = array_merge([
            'options' => [],
        ], $options);

        parent::__construct($default, $options);
    }

    /**
     * Set value
     * @param mixed $value new value
     * @throws \InvalidArgumentException
     */
    public function set($value)
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

    /**
     * Options
     */
    public function options(): array
    {
        return $this->Options['options'];
    }
}
