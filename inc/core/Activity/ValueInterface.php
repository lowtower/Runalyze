<?php

declare(strict_types=1);

/**
 * This file contains class::ValueInterface
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

/**
 * Interface for any value
 *
 * @author Hannes Christiansen <hannes@runalyze.de>
 * @package Runalyze\Activity
 */
interface ValueInterface
{
    /**
     * Label for value
     */
    public function label(): string;

    public function unit(): string;

    /**
     * Set value
     * @return \Runalyze\Activity\ValueInterface $this-reference
     */
    public function set($value): self;

    /**
     * Get value
     * @return mixed
     */
    public function value();

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string;
}
