<?php

namespace Runalyze\Parser\Activity\Common\Data\Pause;

class PauseCollection implements \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * @var Pause[]
     */
    protected $Elements = [];

    /**
     * @param Pause[] $elements
     */
    public function __construct(array $elements = [])
    {
        foreach ($elements as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }

    public function __clone()
    {
        foreach ($this->Elements as $i => $element) {
            $this->Elements[$i] = clone $element;
        }
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->Elements);
    }

    public function add(Pause $pause)
    {
        $this->Elements[] = $pause;
    }

    /**
     * @return Pause[]
     */
    public function getElements()
    {
        return $this->Elements;
    }

    public function isEmpty(): bool
    {
        return $this->Elements === [];
    }

    public function clear()
    {
        $this->Elements = [];
    }

    public function count()
    {
        return count($this->Elements);
    }

    public function offsetExists($offset)
    {
        return isset($this->Elements[$offset]);
    }

    /**
     * @param int $offset
     * @return Pause
     */
    public function offsetGet($offset)
    {
        return $this->Elements[$offset];
    }

    /**
     * @param int|null $offset
     * @param Pause $value
     *
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (! ($value instanceof Pause)) {
            throw new \InvalidArgumentException('Pause collection does only accept instances of Pause as elements.');
        }

        if ($offset === null) {
            $this->Elements[] = $value;
        } else {
            $this->Elements[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->Elements[$offset]);
    }

    public function getTotalDuration(): float
    {
        return array_reduce(
            $this->getElements(),
            function ($carry, Pause $pause): float {
                return $carry + $pause->getDuration();
            },
            0
        );
    }

    public function rebase()
    {
        $this->Elements = array_values($this->Elements);
    }
}
