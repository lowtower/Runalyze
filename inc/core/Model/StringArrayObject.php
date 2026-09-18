<?php

/**
 * This file contains class::StringObject
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Abstract string array object
 *
 * A string object represents a complete object consisting mainly of an array
 * that can be created by strings and be saved as string.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class StringArrayObject extends StringObject
{
    /**
     * Single elements
     * @var mixed
     */
    protected $Elements = [];

    /**
     * Construct
     * @param string|array|null $data string or array
     */
    public function __construct(string|array|null $data = '')
    {
        if (is_array($data)) {
            $this->fromArray($data);
        } elseif (! empty($data)) {
            parent::__construct($data);
        }
    }

    /**
     * @param array $data array of elements
     */
    public function fromArray(array $data)
    {
        $this->Elements = $data;
    }

    public function asArray(): array
    {
        return $this->Elements;
    }

    public function fromString(string $string)
    {
        $json = json_decode($string, true);

        if (is_array($json)) {
            $this->fromArray($json);
        } else {
            $this->clear();
        }
    }

    public function asString(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return json_encode($this->asArray(), JSON_HEX_QUOT);
    }

    public function clear()
    {
        $this->Elements = [];
    }

    /**
     * Number of pauses
     */
    public function num(): int
    {
        return count($this->Elements);
    }

    public function isEmpty(): bool
    {
        return $this->num() === 0;
    }

    /**
     * @param mixed $element
     */
    public function add($element)
    {
        $this->Elements[] = $element;
    }

    /**
     * Get pause
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function at($index)
    {
        if (! isset($this->Elements[$index])) {
            throw new \InvalidArgumentException('Unknown object index.');
        }

        return $this->Elements[$index];
    }
}
