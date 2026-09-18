<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Activity\Splits
 */

namespace Runalyze\Model\Activity\Splits;

use Runalyze\Model\StringArrayObject;

/**
 * Splits entity
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Activity\Splits
 */
class Entity extends StringArrayObject
{
    /**
     * Separator
     * @var string
     */
    public const SEPARATOR = '-';

    /**
     * Single objects
     * @var Split[]
     */
    protected $Elements = [];

    /**
     * Clone object
     */
    public function __clone()
    {
        foreach ($this->Elements as $i => $split) {
            $this->Elements[$i] = clone $split;
        }
    }

    /**
     * @param Split[] $splits
     * @throws \InvalidArgumentException
     */
    public function fromArray(array $splits): void
    {
        $this->clear();

        foreach ($splits as $split) {
            if (! ($split instanceof Split)) {
                throw new \InvalidArgumentException('Array must consist of Split-objects only.');
            }

            if (! $split->isEmpty()) {
                $this->add($split);
            }
        }
    }

    /**
     * As array
     * @return Split[]
     */
    public function asArray(): array
    {
        return $this->Elements;
    }

    public function fromString(string $string): void
    {
        $this->clear();

        $strings = explode(self::SEPARATOR, $string);
        foreach ($strings as $splitString) {
            $Split = new Split();
            $Split->fromString($splitString);

            if (! $Split->isEmpty()) {
                $this->add($Split);
            }
        }
    }

    /**
     * As string
     */
    public function asString(): string
    {
        $array = [];

        foreach ($this->Elements as $Split) {
            $array[] = $Split->asString();
        }

        return implode(self::SEPARATOR, $array);
    }

    /**
     * Add split
     * @param Split $split
     * @throws \InvalidArgumentException
     */
    public function add($split): void
    {
        if (! ($split) instanceof Split) {
            throw new \InvalidArgumentException("Element to add has to be of type 'Split'.");
        }

        parent::add($split);
    }

    /**
     * Get split
     * @throws \InvalidArgumentException
     */
    public function at($index): Split
    {
        return parent::at($index);
    }

    /**
     * Get total time
     */
    public function totalTime(): int
    {
        $time = 0;

        foreach ($this->Elements as $split) {
            $time += $split->time();
        }

        return $time;
    }

    /**
     * Get total distance
     */
    public function totalDistance(): float
    {
        $distance = 0;

        foreach ($this->Elements as $split) {
            $distance += $split->distance();
        }

        return $distance;
    }

    /**
     * Is at least one lap active?
     */
    public function hasActiveLaps(int $num = 1): bool
    {
        $count = 0;

        foreach ($this->Elements as $split) {
            if ($split->isActive()) {
                $count++;

                if ($count === $num) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Are there active and inactive laps?
     */
    public function hasActiveAndInactiveLaps(): bool
    {
        $active = null;

        foreach ($this->Elements as $split) {
            if ($active === null) {
                $active = $split->isActive();
            } elseif ($active != $split->isActive()) {
                return true;
            }
        }

        return false;
    }
}
