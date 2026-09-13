<?php

/**
 * This file contains class::Helper
 * @package Runalyze
 */

use Runalyze\Configuration;

/**
 * Timestamp of the first training
 */
define('START_TIME', Helper::getStartTime());

/**
 * Year of the first training
 */
define('START_YEAR', date('Y', START_TIME));

/**
 * Class for all helper-functions previously done by functions.php
 * @author Hannes Christiansen
 * @package Runalyze
 */
class Helper
{
    /**
     * Trim all values of an array
     */
    public static function arrayTrim(array $array): array
    {
        array_walk($array, 'trimValuesForArray');

        return $array;
    }

    /**
     * Round to the next lowest factor of $roundForInt
     */
    public static function floorFor(float $numberToRound, int $roundForInt): int
    {
        return (int) ($roundForInt * floor($numberToRound / $roundForInt));
    }

    /**
     * Round to the next highest factor of $roundForInt
     */
    public static function ceilFor(float $numberToRound, int $roundForInt): int
    {
        return (int) ($roundForInt * ceil($numberToRound / $roundForInt));
    }

    /**
     * Get a leading 0 if $int is lower than 10
     */
    public static function TwoNumbers(int $int): string
    {
        return ($int < 10) ? '0' . $int : (string) $int;
    }

    /**
     * Get a special $string if $var is not set
     * @param mixed $var
     * @param string $string string to be displayed instead, default: ?
     */
    public static function Unknown($var, string $string = '?'): string
    {
        if (! isset($var) || $var == null) {
            return $string;
        }

        if ((is_numeric($var) && $var != 0) || (! is_numeric($var) && $var != '')) {
            return $var;
        }

        return $string;
    }

    /**
     * Cut a string if it is longer than $cut (default CUT_LENGTH)
     * @param int $cut [optional]
     */
    public static function Cut(string $text, int $cut = 0): string
    {
        if ($cut === 0) {
            $cut = CUT_LENGTH;
        }

        if (mb_strlen($text) >= $cut) {
            return Ajax::tooltip(mb_substr($text, 0, $cut - 3) . '...', $text);
        }

        return $text;
    }

    /**
     * Replace every comma with a point
     */
    public static function CommaToPoint(string $string): string
    {
        return str_replace(',', '.', $string);
    }

    /**
     * Get const for START_TIME
     */
    public static function getStartTime(): int
    {
        if (defined('START_TIME')) {
            return START_TIME;
        }

        if (Configuration::Data()->startTime() == 0 && SessionAccountHandler::isLoggedIn()) {
            return self::recalculateStartTime();
        }

        return Configuration::Data()->startTime();
    }

    /**
     * Recalculate START_TIME
     */
    public static function recalculateStartTime(): int
    {
        $START_TIME = self::calculateStartTime();

        Configuration::Data()->updateStartTime($START_TIME);

        if ($START_TIME === 0) {
            return time();
        }

        return $START_TIME;
    }

    /**
     * Get timestamp of first training
     * @return int   Timestamp
     */
    private static function calculateStartTime(): int
    {
        $data = DB::getInstance()->query('SELECT MIN(`time`) as `time` FROM `' . PREFIX . 'training` WHERE accountid = ' . SessionAccountHandler::getId())->fetch();

        if (isset($data['time']) && $data['time'] == 0) {
            $data = DB::getInstance()->query('SELECT MIN(`time`) as `time` FROM `' . PREFIX . 'training` WHERE `time` != 0 AND accountid = ' . SessionAccountHandler::getId())->fetch();
        }

        if ($data === false || $data['time'] === null) {
            return 0;
        }

        return (int) $data['time'];
    }

    /**
     * Recalculate HF_MAX and HF_REST
     */
    public static function recalculateHFmaxAndHFrest(): void
    {
        self::recalculateHFmax();
        self::recalculateHFrest();
    }

    /**
     * Recalculate HF_MAX
     */
    public static function recalculateHFmax(): int
    {
        $HF_MAX = self::calculateHFmax();

        Configuration::Data()->updateHRmax($HF_MAX);

        return $HF_MAX;
    }

    /**
     * Get the HFmax from user-table
     * @return int   HFmax
     */
    private static function calculateHFmax(): int
    {
        // TODO: Move to class::UserData - possible problem in loading order?
        if (SharedLinker::isOnSharedPage()) {
            $userdata = DB::getInstance()->query('SELECT `pulse_max` FROM `' . PREFIX . 'user` WHERE `accountid`="' . SharedLinker::getUserId() . '" AND `pulse_max` > 0  ORDER BY `time` DESC LIMIT 1')->fetch();
        } else {
            $userdata = DB::getInstance()->query('SELECT `pulse_max` FROM `' . PREFIX . 'user` WHERE `pulse_max` > 0 AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `time` DESC LIMIT 1')->fetch();
        }

        if ($userdata === false || $userdata['pulse_max'] == 0) {
            return 200;
        }

        return (int) $userdata['pulse_max'];
    }

    /**
     * Recalculate HF_REST
     */
    public static function recalculateHFrest(): int
    {
        $HF_REST = self::calculateHFrest();

        Configuration::Data()->updateHRrest($HF_REST);

        return $HF_REST;
    }

    /**
     * Get the HFrest from user-table
     * @return int   HFrest
     */
    private static function calculateHFrest(): int
    {
        // TODO: Move to class::UserData - possible problem in loading order?
        if (SharedLinker::isOnSharedPage()) {
            $userdata = DB::getInstance()->query('SELECT `pulse_rest` FROM `' . PREFIX . 'user` WHERE `accountid`="' . SharedLinker::getUserId() . '" AND `pulse_rest` > 0 AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `time` DESC LIMIT 1')->fetch();
        } else {
            $userdata = DB::getInstance()->query('SELECT `pulse_rest` FROM `' . PREFIX . 'user` WHERE `pulse_rest` > 0 AND accountid = ' . SessionAccountHandler::getId() . ' ORDER BY `time` DESC LIMIT 1')->fetch();
        }

        if ($userdata === false) {
            return 60;
        }

        return (int) $userdata['pulse_rest'];
    }
}

/**
 * Trimmer function for array_walk
 */
function trimValuesForArray(string &$value)
{
    $value = trim($value);
}
