<?php

/**
 * This file contains class::FormularValueParser
 * @package Runalyze\HTML\Formular\Validation
 */

use Runalyze\Activity\Duration;

/**
 * @deprecated since v4.3
 */
class FormularValueParser
{
    /**
     * Parser: timestamp <=> date-string
     */
    public static string $PARSER_DATE = 'date';

    /**
     * Parser: time in seconds <=> time-string
     */
    public static string $PARSER_TIME = 'time';

    /**
     * Parser: check for boolean
     */
    public static string $PARSER_BOOL = 'bool';

    /**
     * Validate post-value for a given key with a given parser
     * @param array<string, mixed> $parserOptions
     */
    public static function validatePost(string $key, ?string $parser, array $parserOptions = []): string|bool
    {
        if ($parser === null) {
            return true;
        }

        if (isset($parserOptions['null']) && $parserOptions['null']) {
            if (isset($_POST[$key]) && ! is_array($_POST[$key]) && (string) $_POST[$key] === '') {
                $_POST[$key] = null;
                return true;
            }
        }

        return match ($parser) {
            self::$PARSER_DATE => self::validateDate($key),
            self::$PARSER_TIME => self::validateTime($key, $parserOptions),
            self::$PARSER_BOOL => self::validateBool($key),
            default => true,
        };
    }

    /**
     * Transform value with a given parser
     * @param mixed $value
     */
    public static function parse(&$value, ?string $parser, array $parserOptions = []): void
    {
        if ($parser === null) {
            return;
        }

        switch ($parser) {
            case self::$PARSER_DATE:
                self::parseDate($value);
                break;
            case self::$PARSER_TIME:
                self::parseTime($value, $parserOptions);
                break;
        }
    }

    /**
     * Validator: boolean
     */
    private static function validateBool(string $key): bool
    {
        $_POST[$key] = isset($_POST[$key]) ? '1' : '0';

        return true;
    }

    /**
     * Validator: date-string => timestamp
     */
    private static function validateDate(string $key): string|bool
    {
        $dateParts = self::removeEmptyValues(explode('.', $_POST[$key]));
        $numParts = count($dateParts);

        if ($numParts === 3) {
            $_POST[$key] = mktime(0, 0, 0, (int) $dateParts[1], (int) $dateParts[0], (int) $dateParts[2]);
        } elseif ($numParts === 2) {
            $_POST[$key] = mktime(0, 0, 0, (int) $dateParts[1], (int) $dateParts[0], (int) date('Y'));
        } else {
            return __('The date could not be parsed.');
        }

        if ($_POST[$key] > time()) {
            return __('The date must not be in the future.');
        }

        return true;
    }

    /**
     * Parse: timestamp => date-string
     */
    private static function parseDate(string &$value): void
    {
        if (is_numeric($value)) {
            $value = date('d.m.Y', (int) $value);
        }
    }

    /**
     * Validator: time-string => time in seconds
     * @param array<string, mixed> $options
     */
    private static function validateTime(string $key, array $options): string|bool
    {
        $Time = new Duration($_POST[$key]);

        $_POST[$key] = $Time->seconds();

        if ($_POST[$key] == 0 && (isset($options['required']) || isset($options['notempty']))) {
            return __('You have to enter a time.');
        }

        return true;
    }

    /**
     * Parse: time in seconds => time-string
     * @param mixed $value
     * @param array<string, mixed> $options
     */
    private static function parseTime(&$value, array $options): void
    {
        if ($value == 0) {
            if (isset($options['hide-empty'])) {
                $value = '';
            } else {
                $value = '0:00:00';
            }
        } else {
            $value = Duration::format($value);
        }
    }

    /**
     * Remove all empty values from array
     */
    private static function removeEmptyValues(array $array): array
    {
        foreach ($array as $key => $value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
