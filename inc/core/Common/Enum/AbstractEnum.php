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

/**
 * Abstract class for enums.
 *
 * Usage:
 * <code>
 * class DaysOfWeek extends AbstractEnum
 * {
 *   const SUNDAY = 0;
 *   const MONDAY = 1;
 *   // ...
 * }
 *
 * DaysOfWeek::isValidName('Monday'); // true
 * DaysOfWeek::isValidName('Monday', true); // false
 * DaysOfWeek::isValidValue(1); // true
 * DaysOfWeek::isValidValue(123); // false
 * DaysOfWeek::getEnum(); // array('SUNDAY' => 0, ...)
 * </code>
 *
 * @see http://stackoverflow.com/a/254543/3449264
 */
abstract class AbstractEnum
{
    private static array $ConstantsCache = [];

    /**
     * Get all constants of called class.
     *
     * This static methods uses an internal cache to not create a reflection on every call.
     *
     * @return array a hash with your constants and their value
     */
    public static function getEnum(): array
    {
        $enumClass = static::class;

        if (! isset(self::$ConstantsCache[$enumClass])) {
            $reflect = new \ReflectionClass($enumClass);
            self::$ConstantsCache[$enumClass] = $reflect->getConstants();
        }

        return self::$ConstantsCache[$enumClass];
    }

    /**
     * Checks whether a constant is valid.
     */
    public static function isValidName(string $name, bool $strict = false): bool
    {
        $constants = self::getEnum();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map(strtolower(...), array_keys($constants));

        return in_array(strtolower($name), $keys, true);
    }

    /**
     * Checks whether a value is defined.
     *
     * @param int|string $value
     */
    public static function isValidValue($value, bool $strict = true): bool
    {
        $values = array_values(self::getEnum());

        return in_array($value, $values, $strict);
    }
}
