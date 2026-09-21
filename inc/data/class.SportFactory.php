<?php

/**
 * This file contains class::SportFactory
 * @package Runalyze\Data\Sport
 */

use Runalyze\Activity\Pace;
use Runalyze\Configuration;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;

/**
 * @deprecated
 */
class SportFactory
{
    /**
     * All sports as array
     */
    private static ?array $AllSports = null;

    /**
     * Data for ID
     */
    public static function DataFor(int $id): array
    {
        $Sports = self::AllSports();

        return $Sports[$id] ?? self::defaultArray();
    }

    /**
     * Array with default values
     *
     * @todo This method should be useless as soon as a DatabaseScheme is used
     * @return array<string, string|int|null>
     */
    private static function defaultArray(): array
    {
        return [
            'name' => '?',
            'img' => '',
            'short' => 0,
            'kcal' => 600,
            'HFavg' => 140,
            'distances' => 0,
            'speed' => PaceEnum::KILOMETER_PER_HOUR,
            'power' => 0,
            'outside' => 0,
            'is_main' => 0,
            'internal_sport_id' => null,
        ];
    }

    /**
     * Get all sports
     */
    public static function AllSports(): array
    {
        if (self::$AllSports === null) {
            self::initAllSports();
        }

        return self::$AllSports;
    }

    /**
     * Initialize internal sports-array from database
     */
    private static function initAllSports(): void
    {
        self::$AllSports = [];
        $sports = self::cacheAllSports();

        foreach ($sports as $sport) {
            self::$AllSports[(string) $sport['id']] = $sport;
        }

        Configuration::ActivityForm()->orderSports()->sort(self::$AllSports);
    }

    /**
     * Cache all sports for user
     */
    private static function cacheAllSports()
    {
        $sports = Cache::get('sport');

        if ($sports === null) {
            $sports = DB::getInstance()->query('SELECT * FROM `' . PREFIX . 'sport` WHERE `accountid` = "' . SessionAccountHandler::getId() . '"')->fetchAll();
            Cache::set('sport', $sports, 3600);
        }

        return $sports;
    }

    /**
     * Use this method after updating sports table
     */
    public static function reInitAllSports(): void
    {
        Cache::delete('sport');

        self::initAllSports();
    }

    /**
     * Get array with all names
     * @return array ids as keys, names as values
     */
    public static function NamesAsArray(): array
    {
        $sports = self::AllSports();

        foreach ($sports as $id => $sport) {
            $sports[$id] = $sport['name'];
        }

        return $sports;
    }

    /**
     * Name of sport
     */
    public static function name(int $sportid): string
    {
        $Sports = self::AllSports();

        if (isset($Sports[$sportid])) {
            return $Sports[$sportid]['name'];
        }

        return __('unknown');
    }

    /**
     * Get speed unit for given sportid
     */
    public static function getSpeedUnitFor(int $ID): int
    {
        $Sports = self::AllSports();

        return (isset($Sports[$ID])) ? (new \Runalyze\Metrics\LegacyUnitConverter())->getLegacyPaceUnit($Sports[$ID]['speed'], true) : Pace::STANDARD;
    }
}
