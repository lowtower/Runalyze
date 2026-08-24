<?php

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

    /**
     * Value
     * @var mixed
     */
    private $Value;

    /**
     * Options
     * @var array
     */
    protected $Options = [];

    /**
     * Construct
     * @param mixed $default
     * @param array $options [optional]
     */
    public function __construct($default, $options = [])
    {
        $this->Value = $default;
        $this->Options = array_merge($this->Options, $options);
    }

    /**
     * Set value
     * @param mixed $value new value
     */
    public function set($value)
    {
        $this->Value = $value;
    }

    /**
     * @param string $valueAsString new value
     */
    public function setFromString($valueAsString)
    {
        $this->set($valueAsString);
    }

    /**
     * @return mixed
     */
    final public function value()
    {
        return $this->Value;
    }

    /**
     * @return string
     */
    public function valueAsString()
    {
        return (string) $this->Value;
    }
}
