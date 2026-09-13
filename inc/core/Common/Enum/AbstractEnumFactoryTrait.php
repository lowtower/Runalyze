<?php

/*
 * This file is part of the Runalyze Age Grade.
 *
 * (c) RUNALYZE <mail@runalyze.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Runalyze\Common\Enum;

trait AbstractEnumFactoryTrait
{
    /**
     * @var array|null
     */
    private static $ClassNames;

    /**
     * @var string
     */
    private static $Namespace = '';

    /**
     * Get object.
     * @param  int|string $enum from internal enum
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    public static function get($enum)
    {
        if (self::$ClassNames === null) {
            self::generateNamespace();
            self::generateClassNamesArray();
        }

        if (! isset(self::$ClassNames[$enum])) {
            throw new \InvalidArgumentException('Invalid enum "' . $enum . '".');
        }

        $className = self::$Namespace . '\\' . self::$ClassNames[$enum];

        return new $className();
    }

    private static function generateNamespace()
    {
        self::$Namespace = substr(static::class, 0, strrpos(static::class, '\\'));
    }

    /**
     * @throws \Exception
     */
    private static function generateClassNamesArray()
    {
        if (! method_exists(static::class, 'getEnum')) {
            throw new \BadMethodCallException('Classes using this trait must have static method getEnum().');
        }

        self::$ClassNames = array_map(fn ($v): string => str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $v)))), array_flip(self::getEnum()));
    }
}
