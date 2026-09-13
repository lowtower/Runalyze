<?php

declare(strict_types=1);

/**
 * This file contains interface::Loopable
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Loopable object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
interface Loopable
{
    /**
     * Number of points
     */
    public function num(): int;

    /**
     * Value at
     *
     * Remark: This method may throw index offsets.
     * @param int $key enum
     * @return mixed
     */
    public function at(int $index, $key);

    /**
     * Get array for this key
     * @return array
     * @throws \InvalidArgumentException
     */
    public function get(string $key);

    /**
     * Has array for this key
     * @throws \InvalidArgumentException
     */
    public function has(string $key): bool;
}
