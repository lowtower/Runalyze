<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Abstract entity
 *
 * An object represents a set of properties, e.g. a row from database.
 * The internal data array contains all raw values, only arrays are automatically transformed.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class Entity
{
    /**
     * Array separator
     * @var string
     */
    public const ARRAY_SEPARATOR = '|';

    /**
     * Raw data
     */
    protected array $Data = [];

    /**
     * Number of data points
     */
    protected int $numberOfPoints = 0;

    /**
     * Flag: ensure arrays to be equally sized
     */
    protected bool $checkArraySizes = false;

    public function __construct(array $data = [])
    {
        $this->readData($data);

        if ($this->checkArraySizes) {
            $this->checkArraySizes();
        } else {
            $this->count();
        }
    }

    protected function cloneInternalObjects(): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (is_object($value)) {
                $this->{$property} = clone $value;
            }
        }
    }

    final protected function readData(array $data): void
    {
        foreach ($this->properties() as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $this->Data[$key] = $this->isArray($key) && ! is_array($data[$key]) ? self::explode($data[$key]) : $data[$key];
            } else {
                $this->Data[$key] = $this->isArray($key) ? [] : ($this->canBeNull($key) ? null : '');
            }
        }
    }

    protected function count(): void
    {
        $this->numberOfPoints = 0;

        foreach ($this->properties() as $key) {
            if ($this->isArray($key)) {
                $num = count($this->Data[$key]);

                if ($num > $this->numberOfPoints) {
                    $this->numberOfPoints = $num;
                }
            }
        }
    }

    /**
     * Number of points
     */
    public function num(): int
    {
        return $this->numberOfPoints;
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkArraySizes(): void
    {
        $this->numberOfPoints = 0;

        foreach ($this->properties() as $key) {
            if ($this->isArray($key)) {
                try {
                    $this->checkArraySize(count($this->Data[$key]));
                } catch (\RuntimeException $E) {
                    throw new \RuntimeException($E->getMessage() . ' (for ' . $key . ')', $E->getCode(), $E);
                }
            }
        }
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkArraySize(int $num): void
    {
        if ($num === 0) {
            return;
        }

        if ($this->numberOfPoints > 0 && $num !== $this->numberOfPoints) {
            throw new \RuntimeException('Data arrays must be of the same size. (' . $num . ' != ' . $this->numberOfPoints . ')');
        }

        $this->numberOfPoints = $num;
    }

    abstract public function properties(): array;

    /**
     * Synchronize internal models
     */
    public function synchronize(): void {}

    /**
     * Ensure numeric values
     * @param array|string $keyOrKeys key or array of keys to be checked
     */
    protected function ensureNumericValue(array|string $keyOrKeys, int|float $defaultValue = 0): void
    {
        if (! is_array($keyOrKeys)) {
            $keyOrKeys = [$keyOrKeys];
        }

        foreach ($keyOrKeys as $key) {
            if (array_key_exists($key, $this->Data)) {
                if (! is_numeric($this->Data[$key])) {
                    if (is_bool($this->Data[$key])) {
                        $this->Data[$key] = (int) $this->Data[$key];
                    } else {
                        $this->Data[$key] = $defaultValue;
                    }
                } else {
                    $this->Data[$key] = (float) $this->Data[$key];
                }
            }
        }
    }

    /**
     * Transforms empty strings to null
     * @param array|string $keyOrKeys key or array of keys to be checked
     */
    protected function ensureNullIfEmpty(array|string $keyOrKeys, bool $ensureNumericIfNotEmpty = false, bool $treatZeroAsNull = false): void
    {
        if (! is_array($keyOrKeys)) {
            $keyOrKeys = [$keyOrKeys];
        }

        foreach ($keyOrKeys as $key) {
            if (array_key_exists($key, $this->Data)) {
                if ($this->Data[$key] === '' || $this->Data[$key] === null || ($treatZeroAsNull && $this->Data[$key] == 0) || $this->Data[$key] === []) {
                    $this->Data[$key] = null;
                } elseif ($ensureNumericIfNotEmpty) {
                    $this->Data[$key] = (float) $this->Data[$key];
                }
            }
        }
    }

    protected function canSet(string $key): bool
    {
        return true;
    }

    public function isArray(string $key): bool
    {
        return false;
    }

    protected function canBeNull(string $key): bool
    {
        return false;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function set(string $key, mixed $value): void
    {
        if (! array_key_exists($key, $this->Data)) {
            throw new \InvalidArgumentException('Unkown data index "' . $key . '".');
        }

        if (! $this->canSet($key)) {
            throw new \InvalidArgumentException('"' . $key . '" can not be set.');
        }

        if ($this->isArray($key) && ! is_array($value) && (! $this->canBeNull($key) || $value !== null)) {
            throw new \RuntimeException('Value "' . $key . '" must be provided as array.');
        }

        $this->Data[$key] = $value;

        if ($this->isArray($key)) {
            $this->handleNewArraySize($value === null ? 0 : count($this->Data[$key]));
        }
    }

    protected function handleNewArraySize(int $num): void
    {
        if ($num !== $this->numberOfPoints) {
            if ($this->checkArraySizes) {
                $this->checkArraySizes();
            } else {
                $this->count();
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function get(string $key): mixed
    {
        if (! array_key_exists($key, $this->Data)) {
            throw new \InvalidArgumentException('Unknown data index "' . $key . '".');
        }

        return $this->Data[$key];
    }

    /**
     * Has array for this key
     * @throws \InvalidArgumentException
     */
    public function has(string $key): bool
    {
        if (! array_key_exists($key, $this->Data)) {
            throw new \InvalidArgumentException('Unknown data index "' . $key . '".');
        }

        return ! empty($this->Data[$key]);
    }

    public function clear(): void
    {
        foreach ($this->properties() as $key) {
            if ($this->isArray($key)) {
                $this->Data[$key] = [];
            } else {
                $this->Data[$key] = '';
            }
        }

        $this->numberOfPoints = 0;
    }

    /**
     * Is this object empty?
     */
    public function isEmpty(): bool
    {
        return array_all($this->properties(), fn ($key): bool => empty($this->Data[$key]) || $this->ignoreNonEmptyValue($key));
    }

    /**
     * Ignore a key while checking for emptiness
     */
    protected function ignoreNonEmptyValue(string $key): bool
    {
        return false;
    }

    /**
     * Get complete data
     */
    public function completeData(): array
    {
        return $this->Data;
    }

    /**
     * Explode string to array
     */
    public static function explode(string $string): array
    {
        return explode(self::ARRAY_SEPARATOR, $string);
    }

    /**
     * Implode array to string
     */
    public static function implode(array $data): string
    {
        return implode(self::ARRAY_SEPARATOR, $data);
    }
}
