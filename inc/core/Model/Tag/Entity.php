<?php

declare(strict_types=1);

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Tag
 */

namespace Runalyze\Model\Tag;

use Runalyze\Model;

/**
 * Tag entity
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Model\Tag
 */
class Entity extends Model\EntityWithID
{
    /**
     * Key: tag
     */
    public const string TAG = 'tag';

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::TAG,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return self::allDatabaseProperties();
    }

    public function tag(): string
    {
        return $this->Data[self::TAG];
    }
}
