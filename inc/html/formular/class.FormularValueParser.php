<?php

/**
 * This file contains class::FormularValueParser
 * @package Runalyze\HTML\Formular\Validation
 */

use Runalyze\Activity\Duration;

/**
 * @deprecated since v4.3
 */
class FormularValueParser {
	/**
	 * Parser: timestamp <=> date-string
	 * @var string
	 */
	public static $PARSER_DATE = 'date';

	/**
	 * Parser: time in seconds <=> time-string
	 * @var string
	 */
	public static $PARSER_TIME = 'time';

	/**
	 * Parser: check for boolean
	 * @var string
	 */
	public static $PARSER_BOOL = 'bool';

    /**
     * Validate post-value for a given key with a given parser
     * @param string $key
     * @param string $parser
     * @param array<string, mixed> $parserOptions
     * @return bool
     */
    public static function validatePost($key, $parser, array $parserOptions = array()) {
		if (null === $parser)
			return true;

		if (isset($parserOptions['null']) && $parserOptions['null']) {
			if (isset($_POST[$key]) && ! is_array($_POST[$key]) && (string) $_POST[$key] === '') {
				$_POST[$key] = null;
				return true;
			}
		}

		switch ($parser) {
			case self::$PARSER_DATE:
				return self::validateDate($key);
			case self::$PARSER_TIME:
				return self::validateTime($key, $parserOptions);
			case self::$PARSER_BOOL:
				return self::validateBool($key);

			default:
				return true;
		}
	}

    /**
     * Transform value with a given parser
     * @param mixed $value
     * @param string $parser
     */
    public static function parse(&$value, $parser, array $parserOptions = array()) {
		if (null === $parser)
			return;

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
     * @param string $key
     */
    private static function validateBool($key): bool {
		$_POST[$key] = isset($_POST[$key]) ? '1' : '0';

		return true;
	}

	/**
	 * Validator: date-string => timestamp
	 * @param string $key
	 * @return bool
	 */
	private static function validateDate($key) {
		$dateParts = self::removeEmptyValues(explode('.', $_POST[$key]));
		$numParts  = count($dateParts);

		if ($numParts === 3) {
			$_POST[$key] = mktime(0, 0, 0, (int) $dateParts[1], (int) $dateParts[0], (int) $dateParts[2]);
		} elseif ($numParts === 2) {
			$_POST[$key] = mktime(0, 0, 0, (int) $dateParts[1], (int) $dateParts[0], date('Y'));
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
	 * @param string $value
	 */
	private static function parseDate(&$value) {
		if (is_numeric($value))
			$value = date('d.m.Y', $value);
	}

    /**
     * Validator: time-string => time in seconds
     * @param string $key
     * @param array<string, mixed> $options
     * @return bool
     */
    private static function validateTime($key, array $options) {
		$Time = new Duration($_POST[$key]);

		$_POST[$key] = $Time->seconds();

		if ($_POST[$key] == 0 && (isset($options['required']) || isset($options['notempty'])))
			return __('You have to enter a time.');

		return true;
	}

    /**
     * Parse: time in seconds => time-string
     * @param mixed $value
     * @param array<string, mixed> $options
     */
    private static function parseTime(&$value, array $options) {
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
     * @return array
     */
    private static function removeEmptyValues(array $array) {
		foreach ($array as $key => $value)
			if (empty($value))
				unset($array[$key]);

		return $array;
	}
}
