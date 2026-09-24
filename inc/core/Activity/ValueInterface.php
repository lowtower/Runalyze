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
    public function label(): string;

    public function unit(): string;

    public function set(mixed $value): self;

    public function value(): mixed;

    public function string(bool $withUnit = true): string;
}
